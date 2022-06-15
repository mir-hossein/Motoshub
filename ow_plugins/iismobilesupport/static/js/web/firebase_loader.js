function sendWebTokenToServer(token){
    $.ajax({
        url: '/setWebToken',
    type: 'POST',
    data: {'token': token},
        dataType: 'json'
    });
}
function loadWebFCM(webkey) {
    messaging.usePublicVapidKey(webkey);
    var notification_request_granted = false;
    Notification.requestPermission().then((permission) => {
        if (permission === 'granted') {
            messaging.getToken().then((currentToken) => {
                if (currentToken) {
                    sendWebTokenToServer(currentToken);
                } else {
                    console.log('No Instance ID token available. Request permission to generate one.');
                    // updateUIForPushPermissionRequired();
                }
            }).catch((err) => {
                console.log('An error occurred while retrieving token. ', err);
            });
            notification_request_granted = true;
        } else {
            notification_request_granted = false;
        }
    });
}