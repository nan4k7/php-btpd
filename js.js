function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

function setCookie (name, value) {
    var argv = setCookie.arguments;
    var argc = setCookie.arguments.length;
    var expires = (argc > 2) ? argv[2] : null;
    var path = (argc > 3) ? argv[3] : null;
    var domain = (argc > 4) ? argv[4] : null;
    var secure = (argc > 5) ? argv[5] : false;
    document.cookie = name + '=' + escape(value) +
        ((expires == null) ? '' : ('; expires=' + expires.toGMTString())) +
        ((path == null) ? '' : ('; path=' + path)) +
        ((domain == null) ? '' : ('; domain=' + domain)) +
        ((secure == true) ? '; secure' : '');
}

function deleteCookie (name) {
    var exp = new Date();
    exp.setTime(exp.getTime() - 1);
    var cval = readCookie(name);
    document.cookie = name + '=' + cval + '; expires=' + exp.toGMTString();
}

var countdown_time = 0;
var timer;
function countdown() {
    countdown_time--;
    var sec = getElement('sec');
    if (countdown_time >= 0) {
	timer = setTimeout('countdown()',1000);
	sec.innerHTML = 'Refresh in ' + countdown_time + ' sec.';
    } else {
	sec.innerHTML = 'Refreshing....';
	clearTimeout(timer);
	window.location.reload(true);
    }
}

function set_refresh(val) {
    if (val > 0) {
	clearTimeout(timer);
	setCookie('refresh', val);
	countdown_time = val;
	countdown();
    } else {
	deleteCookie('refresh');
	clearTimeout(timer);
	var sec = getElement('sec');
	sec.innerHTML = 'Refresh:';
    }
}

function getElement(eID){
  return (document.getElementById) ? document.getElementById(eID) : document.all[eID];
}

function open_login_form() {
    var login_form = getElement('login');
    login_form.style.display = (login_form.style.display == 'block' ? 'none' : 'block');
}
