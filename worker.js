importScripts('https://www.gstatic.com/firebasejs/4.10.1/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/4.10.1/firebase-messaging.js');

const config = {
    apiKey: 'AIzaSyB9eqpS9EZYOk_9Yok8Rm-g3nBjqs0W7lw',
    authDomain: 'qtool-196208.firebaseapp.com',
    databaseURL: 'https://qtool-196208.firebaseio.com',
    projectId: 'qtool-196208',
    storageBucket: 'qtool-196208.appspot.com',
    messagingSenderId: '291581890497'
};

firebase.initializeApp(config);

const messaging = firebase.messaging();

messaging.setBackgroundMessageHandler(function(payload) {
  const notificationTitle = payload.data.title;
  const notificationOptions = {
        body: payload.data.body,
        icon: payload.data.icon,
        image: payload.data.image
  };
  return self.registration.showNotification(notificationTitle, notificationOptions);
});
