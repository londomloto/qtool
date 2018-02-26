importScripts('https://www.gstatic.com/firebasejs/4.10.1/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/4.10.1/firebase-messaging.js');

const config = {
    apiKey: "AIzaSyB9eqpS9EZYOk_9Yok8Rm-g3nBjqs0W7lw",
    authDomain: "qtool-196208.firebaseapp.com",
    databaseURL: "https://qtool-196208.firebaseio.com",
    projectId: "qtool-196208",
    storageBucket: "",
    messagingSenderId: "291581890497"
};

firebase.initializeApp(config);

const messaging = firebase.messaging();

messaging.setBackgroundMessageHandler(function(payload) {
  
  const notificationTitle = 'Notification';
  const notificationOptions = {
        body: 'You have new notification',
        icon: 'img/manifest/icon-48x48.png'
  };
  return self.registration.showNotification(notificationTitle, notificationOptions);
});
