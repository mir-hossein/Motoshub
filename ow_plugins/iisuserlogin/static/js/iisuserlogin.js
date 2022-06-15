var userLogin = false;
var messageShowed = false;
OW.bind("userLoggedOutPopUp", function(item) {
    if(typeof item.data.isLoggedOut != 'undefined' && !item.data.isLoggedOut)
        userLogin=true;
    else {
        if (typeof item.data.isLoggedOut != 'undefined' && item.data.isLoggedOut && userLogin && !messageShowed) {
            messageShowed = true;
            var jc = $.confirm(OW.getLanguageText('iisuserlogin', 'loggedOut'));
            jc.buttons.ok.action = function () {
                window.location.href = item.data.signInUrl;
            };
            jc.buttons.close.action = function () {
                // window.location.href = item.data.homeUrl;
                userLogin = false;
            };
        }
    }
});
