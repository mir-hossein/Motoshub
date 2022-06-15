/**
 * Here is is the code snippet to initialize Firebase Messaging in the Service
 * Worker when your app is not hosted on Firebase Hosting.
*/
 // [START initialize_firebase_in_sw]
 // Give the service worker access to Firebase Messaging.
 // Note that you can only use Firebase Messaging here, other Firebase libraries
 // are not available in the service worker.
importScripts('/__/firebase/6.2.0/firebase-app.js');
importScripts('/__/firebase/6.2.0/firebase-messaging.js');
importScripts('/__/firebase/init.js');

// If you would like to customize notifications that are received in the
// background (Web app is closed or not in browser focus) then you should
// implement this optional method.
// [START background_handler]
messaging.setBackgroundMessageHandler(function(payload) {
    // console.log(payload);
    if(payload.data.notificationId <= payload.data.lastViewedNotification){
        return;
    }
    if(payload.data.title === '' || payload.data.description === ''){
        return;
    }
    // Customize notification here
    var notificationTitle = payload.data.title;
    var notificationOptions = {
        body: payload.data.description,
        icon: payload.data.avatarUrl,
        // image: payload.data.avatarUrl, header
        badge: payload.data.avatarUrl,
        vibrate: [500,110,500,110,450,110,200,110,170,40,450,110,200,110,170,40,500],
        dir: "rtl",
        data: { url:payload.data.url }, //the url which we gonna use later
        click_action: payload.data.url
        //actions: [{action: "open_url", title: "Read Now"}]
    };

    return self.registration.showNotification(notificationTitle, notificationOptions);
});
// [END background_handler]

// On notification click
self.addEventListener('notificationclick', function(event) {
    // console.log(event);
    event.notification.close();

    // This looks to see if the current is already open and
    // focuses if it is
    event.waitUntil(clients.matchAll({
        type: "window"
    }).then(function (clientList) {
        if (clients.openWindow)
            return clients.openWindow(event.notification.data.url);
    }));
});