<?php
namespace Wovax\IDX;
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit();
}

use Wovax\IDX\Settings\InitialSetup;

class UserModal {
	public function __construct() {
	}
	public function ajaxLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_die(-1, 403);
        }
		// First check the nonce, if it this will exit
		check_ajax_referer('wovax-idx-user-modal-login', 'wp_ajax_nonce');
		header('Content-Type: application/json');
		$info = array();
		$info['user_login']    = trim($_POST['login']);
		$info['user_password'] = strval($_POST['password']);
        $info['remember']      = true;
        $response              = array(
			'success' => false,
			'msg'     => 'Wrong username or password.'
        );
        if(strlen($info['user_login']) < 1) {
            $response['msg'] = 'Please provide a username or email to login.';
            echo json_encode($response);
            die();
        }
        if(strlen($info['user_password']) < 1) {
            $response['msg'] = 'Please provide a password to login.';
            echo json_encode($response);
            die();
        }
		$user_signon = wp_signon($info, false);
		if(!is_wp_error($user_signon)) {
			$response['msg'] = 'Login successful, reloading...';
            $response['success'] = true;
            wp_set_current_user($user_signon->ID);
            wp_set_auth_cookie($user_signon->ID, true);
		}
		echo json_encode($response);
		die();
	}
	public function ajaxSignup() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_die(-1, 403);
        }
		check_ajax_referer('wovax-idx-user-modal-signup', 'wp_ajax_nonce');
		header('Content-Type: application/json');
		$response = $this->processSignup();
		echo json_encode($response);
		die();
	}
	public function cookie() {
		$name = 'WovaxPageView';
		$has  = isset($_COOKIE[$name]);
		$val  = 0;
		if(is_user_logged_in()) {
			if($has) { // Clear the cookie.
				setcookie($name, 0, time() - 3600, '/');
			}
			return;
		}
		if(!$this->isDetailsPage()) {
			return; // Don't count other pages.
		}
		// get the cookie value.
		if($has) {
			$tmp = intval($_COOKIE[$name]);
			if($tmp < 0) {
				$tmp = 0;
			}
			$val = $tmp;
		}
		$val++;
		// Expires in 6 hours
		setcookie($name, $val, time() + (6 * 60 * 60), '/');
	}
	public function forceLogin() {
		$force = get_option('wovax-idx-settings-users-registration-force', 'no') === 'yes';
		$count = intval(get_option('wovax-idx-settings-users-registration-force-count', 10));
		$val   = 0;
		if(isset($_COOKIE['WovaxPageView'])) {
			$val = intval($_COOKIE['WovaxPageView']);
		}
		return $this->isDetailsPage() && $force && ($val >= $count);
	}
	private function processSignup() {
		$response = array(
			'success'  => false,
			'msg'      => 'Unknown error.',
			'field' => ''
		);
		// Validate username.
		$username = trim($_POST['username']);
		$response['field'] = 'username';
		if(strlen($username) < 1) {
			$response['msg'] = 'Please specify a username.';
			return $response;
		}
		if(strlen($username) > 50) {
			$response['msg'] = 'This username is too long! The max length is 50 characters.';
			return $response;
		}
		if(username_exists($username)) {
			$response['msg'] = 'This username is already in use!';
			return $response;
		}
		// Validate password
		$password     = strval($_POST['password']);
		$password_con = strval($_POST['password_con']);
		if(strlen($password) < 8) {
			$response['msg']      = 'The password must be at least 8 characters long.';
			$response['field'] = 'password';
			return $response;
		}
		if($password_con !== $password) {
			$response['msg']      = 'The password confirmation failed please make them match.';
			$response['field'] = 'password_con';
			return $response;
		}
		// Validate email
		$email = trim($_POST['email']);
		$response['field'] = 'email';
		if(strlen($email) < 1) {
			$response['msg'] = 'An email address is required.';
			return $response;
		}
		if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$response['msg'] = 'Unfortunately, this appears to be an invalid email address.';
			return $response;
		}
		if(email_exists($email)) {
			$response['msg'] = 'That email is already registered to a user.';
			return $response;
		}
		$user_id = wp_create_user($username, $password, $email);
        if(is_wp_error($user_id)) {
			$response['msg'] = $user_id->get_error_message();
			$field = '';
			switch($user_id->get_error_codes()) {
				case 'empty_user_login':
				case 'user_login_too_long':
				case 'existing_user_login':
				case 'invalid_username':
					$field = 'username';
					break;
				case 'existing_user_email':
					$field = 'email';
					break;
			}
			$response['field'] = $field;
			return $response;
		}
		$response['msg']     = 'Created user successfully, reloading...';
		$response['success'] = true;
		unset($response['field']);
		$first = ucwords(trim($_POST['first']));
		$last  = ucwords(trim($_POST['last']));
		$phone = trim($_POST['phone']);
		$full  = trim($first.' '.$last);
		// Add additional user meta.
		if(strlen($full) > 0) {
			$args = array(
				'ID'            => $user_id,
				'display_name'  => $full,
				'nickname'      => $full,
				'user_nicename' => $full
			);
			if(strlen($first) > 0) {
				$args['first_name'] = $first;
			}
			if(strlen($last) > 0) {
				$args['last_name'] = $last;
			}
			wp_update_user($args);
		}
		update_user_meta($user_id, 'phone', $phone);
        update_user_meta($user_id, 'wovax-idx-favorites', json_decode(array()));
        $info = array();
        $info['user_login'] = $username;
        $info['user_password'] = $password;
        $info['remember'] = true;
        $user = wp_signon($info, false);
        if (is_wp_error($user)) {
            $response['msg']     = 'Signup Login Failed, reload and try again...';
            $response['success'] = false;
        }
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);
		return $response;
	}
	private function isDetailsPage() {
		$opt = new InitialSetup();
		return $opt->detailPage() === get_the_ID();
	}
	private function isSearchPage() {
		$opt = new InitialSetup();
		return $opt->searchPage() === get_the_ID();
	}
	public function renderModal($content) {
		if(
			is_user_logged_in() ||
			// Worry about search later
			//!($this->isDetailsPage() || $this->isSearchPage())
			!$this->isDetailsPage()
		) {
			// Don't render if logged in or the page is not search or details
			return $content;
		}
        $referer      = json_encode($_SERVER['REQUEST_URI']);
		$nonce_login  = json_encode(wp_create_nonce('wovax-idx-user-modal-login'));
        $nonce_signup = json_encode(wp_create_nonce('wovax-idx-user-modal-signup'));
		$ajax_url     = json_encode(admin_url('admin-ajax.php'));
		$force        = json_encode($this->forceLogin());
		$home_url     = get_home_url();
		$html  = <<<HTML
<div id="wovax-idx-sign-in-modal" style="display:none;" class="wxmodal">
	<nav>
		<ul class="wovax-idx-modal-tabs">
			<li><a class="wovax-idx-modal-tab-link" id="wovax-sign-up-tab" onclick="WovaxIdxUserModal.showSignup();" href="#wovax-idx-sign-up">Sign Up</a></li>
			<li><a class="wovax-idx-modal-tab-link" id="wovax-sign-in-tab" onclick="WovaxIdxUserModal.showSignin();" href="#wovax-idx-sign-in">Sign In</a></li>
		</ul>
	</nav>
	<div id="wovax-sign-up-content" class="wovax-idx-modal-tab-content">
		<form class="wovax-idx-form" id="wovax-idx-form-1" action="#" method="post">
			<div class="wovax-idx-section">
				<label for="first_name_signup">First Name</label>
				<input type="text" id="first_name_signup"/>
				<label for="last_name_signup">Last Name</label>
				<input type="text" id="last_name_signup"/>
				<label for="email_signup">Email</label>
				<input type="email" id="email_signup" required />
				<label for="phone_signup">Phone</label>
				<input type="text" id="phone_signup"/>
				<label for="username_signup">Username</label>
				<input type="text" id="username_signup" required />
				<label for="password_signup">Password</label>
				<input type="password" id="password_signup" required />
				<label for="password_verify_signup">Verify Password</label>
				<input type="password" id="password_verify_signup" required />
				<div class="wovax-idx-form-button">
					<a style="cursor: pointer;" id="signup_button" type="submit" class="wovax-idx-button wovax-idx-button-highlight" onclick="WovaxIdxUserModal.signup();">Sign Up</a>
				</div>
			</div>
		</form>
	</div>
	<div id="wovax-sign-in-content" class="wovax-idx-modal-tab-content">
		<form class="wovax-idx-form" id="wovax-idx-form-2" action="#" method="post">
			<div class="wovax-idx-section">
				<label for="user_sign_in">Username or Email</label>
				<input type="text" id="user_sign_in" required />
				<label for="password_sign_in">Password</label>
				<input type="password" id="password_sign_in" required />
				<div class="wovax-idx-form-button">
					<a style="cursor: pointer;" id="sign_in_button" type="submit" class="wovax-idx-button wovax-idx-button-highlight" onclick="WovaxIdxUserModal.login();">Sign In</a>
				</div>
			</div>
		</form>
	</div>
</div>
<style>
/* Give tab content a fade in */
@-webkit-keyframes wovaxIdxTabFade {
    from {opacity: 0;}
    to {opacity: 1;}
}
@keyframes wovaxIdxTabFade {
    from {opacity: 0;}
    to {opacity: 1;}
}
/* Style for tab content */
.wovax-idx-modal-tab-content {
    display: none;
    -webkit-animation: wovaxIdxTabFade 1s;
    animation: wovaxIdxTabFade 1s;
}
/* Tab nav */
nav ul.wovax-idx-modal-tabs {
	display: flex;
	font-size: 15px;
	line-height: 60px;
}
nav ul.wovax-idx-modal-tabs li {
	margin: 0 15px;
}
a.wovax-idx-modal-tab-link {
	color: #666;
}
a.wovax-idx-modal-tab-link:hover {
	color: #08f;
}
/* Tab Message Block */
div.wovax-idx-modal-tab-msg {
	box-sizing: border-box;
    position: relative;
}
.wovax-idx-modal-tab-msg div:nth-child(1) {
	position: absolute;
	top: 1px;
    left: 10px;
	padding: 6px 3px 3px;
	font-size: 12px;
	line-height: 12px;
    color: #bbb;
}
.wovax-idx-modal-tab-msg div:nth-child(2) {
    font-size: 16px;
	padding: 18px 12px 6px 12px;
}
div.wovax-idx-modal-tab-msg-err {
	color: #900;
    background-color: #FFA1A1;
}
.wovax-idx-modal-tab-msg-err div:nth-child(1) {
    color: #c55;
}
</style>
<script>
var WovaxIdxUserModal = {
	removeHash() {
		if ("replaceState" in history) {
			var loc = window.location;
			history.replaceState("", document.title, loc.pathname + loc.search);
		} else {
			var scroll_t = document.body.scrollTop;
			var scroll_l = document.body.scrollLeft;
			loc.hash = "";
			document.body.scrollTop = scroll_t;
			document.body.scrollLeft = scroll_l;
		}
	},
	setActive() {
		// make right clicking to open in a new tab work.
		if(window.location.hash === '#wovax-idx-sign-in') {
			this.showSignin();
		} else {
			this.showSignup();
		}
	},
	showTab: function(current, content) {
		var all_content = document.getElementsByClassName('wovax-idx-modal-tab-content');
		var all_links   = document.getElementsByClassName('wovax-idx-modal-tab-link');
		Array.prototype.forEach.call(all_content, function(el) {
			el.style.display = 'none';
		});
		Array.prototype.forEach.call(all_links, function(el) {
			el.parentElement.removeAttribute("style");
			el.removeAttribute("style");
		});
		var parent = current.parentElement;
		content.style.display     = 'block';
		current.style.color       = '#007aff';
		parent.style.borderBottom = '2px solid #007aff';
	},
	showSignin() {
		this.showTab(
			document.getElementById('wovax-sign-in-tab'),
			document.getElementById('wovax-sign-in-content')
		);
	},
	showSignup() {
		this.showTab(
			document.getElementById('wovax-sign-up-tab'),
			document.getElementById('wovax-sign-up-content')
		);
	},
	initModal(forced, message) {
		var tabHashes = ['#wovax-idx-sign-in', '#wovax-idx-sign-up'];
		// if the hash exists display the modal.
		var show      = tabHashes.indexOf(window.location.hash) > -1 || forced;
		if(show) {
			WovaxIdxUserModal.showModal(forced, message);
		}
	},
	showModal(forced, message, err) {
		this.setActive();
		var msg = typeof message === 'string' ? message : '';
		var props     = {
			fadeDuration: 200
		};
		if(forced) {
			props.escapeClose = false;
			props.clickClose  = false;
			props.showClose   = false;
			if(msg.length < 1) {
				msg = '<span style="font-weight: bold;">Sign Up</span> ';
				msg += 'or <span style="font-weight: bold;">Sign In</span> ';
				msg += 'to continue or <a href=\"$home_url\">return</a> to the home page.';
			}

		}
		if(msg.length > 0) {
			var el = document.getElementById('wovax-sign-up-content');
			this.createMsgNode(el, '', msg, err);
		}
		jQuery('#wovax-idx-sign-in-modal').wxmodal(props);
    },
    createMsgNode(target, title, msg, err) {
        var old = document.getElementById('wovax-idx-modal-msg');
        if(old !== null) {
            old.remove();
        }
        var node = document.createElement('div');
        node.innerHTML = '<div>'+ title + '</div><div>' + msg + '</div>';
		node.className = 'wovax-idx-modal-tab-msg';
		node.id = 'wovax-idx-modal-msg';
		if(err === true) {
			node.className += ' wovax-idx-modal-tab-msg-err';
		}
        var parent = target.parentNode;
        parent.insertBefore(node, target);
        return node;
    },
    login() {
        var el    = document.getElementById('user_sign_in').parentNode;
        var self  = this;
        var body  = {
            action: 'wovax_idx_user_modal_login',
            wp_ajax_nonce: $nonce_login,
            _wp_http_referer: $referer,
            login: document.getElementById('user_sign_in').value,
            password: document.getElementById('password_sign_in').value
        };
        var success = function(msg, field) {
			self.createMsgNode(el, 'Logging in:', msg, false);
			self.removeHash();
            location.reload(true);
        };
        var failure = function(msg, field) {
            self.createMsgNode(el, 'Please fix the following:', msg, true);
        };
        this.postLoginData(body, success, failure);
    },
    signup() {
        var self  = this;
        var nodes = {
            email: document.getElementById('email_signup'),
            first: document.getElementById('first_name_signup'),
            last:  document.getElementById('last_name_signup'),
            password: document.getElementById('password_signup'),
            password_con: document.getElementById('password_verify_signup'),
            phone: document.getElementById('phone_signup'),
            username: document.getElementById('username_signup')
        };
        var body  = {
            action: 'wovax_idx_user_modal_signup',
            wp_ajax_nonce: $nonce_signup,
            _wp_http_referer: $referer,
        };
        Object.keys(nodes).map(function(key) {
            body[key] = nodes[key].value;
        });
        var tar = nodes.first.parentNode;
        var success = function(msg, field) {
			self.createMsgNode(tar, 'Creating User:', msg, false);
			self.removeHash();
            location.reload(true);
        };
        var failure = function(msg, field) {
            if(nodes.hasOwnProperty(field)) {
                var tar = nodes[field].parentNode;
            }
            self.createMsgNode(tar, 'Please fix the following:', msg, true);
        };
        this.postLoginData(body, success, failure);
    },
    postLoginData(body, success, failure) {
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) {
                return; // request is not complete
            }
            var failed  = true;
            var msg     = 'Something went wrong please try again.';
            var field   = '';
            if (xhr.status >= 200 && xhr.status < 300) {
                var data = JSON.parse(xhr.responseText);
                if(data.hasOwnProperty('msg')) {
                    msg = data.msg;
                }
                if(data.hasOwnProperty('field')) {
                    field = data.field;
                }
                if(data.hasOwnProperty('success') && data.success === true) {
                    success(msg);
                    return;
                }
            }
            failure(msg, field);
        };
        xhr.open('POST', $ajax_url);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        var parts = Object.keys(body).map(function(key) {
            var val = encodeURIComponent(key) + '=';
            val += encodeURIComponent(body[key]);
            return val;
        });
        xhr.send(parts.join('&'));
    }
};
WovaxIdxUserModal.initModal($force);
</script>
HTML;
		return $html.$content;
	}
	public static function init() {
		$self = new self();
        add_action('wp_ajax_nopriv_wovax_idx_user_modal_login', array($self, 'ajaxLogin'));
        add_action('wp_ajax_nopriv_wovax_idx_user_modal_signup', array($self, 'ajaxSignup'));
		// if some reason one sumbits data while logged in we probably want it to work
        add_action('wp_ajax_wovax_idx_user_modal_login', array($self, 'ajaxLogin'));
		add_action('wp_ajax_wovax_idx_user_modal_signup', array($self, 'ajaxSignup'));
		add_filter('the_content', array($self, 'renderModal'));
		add_action('wp', array($self, 'cookie'));
    }
}
add_action('plugins_loaded',  __NAMESPACE__.'\\UserModal::init');