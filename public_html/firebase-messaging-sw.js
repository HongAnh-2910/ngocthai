// Give the service worker access to Firebase Messaging.
// Note that you can only use Firebase Messaging here, other Firebase libraries
// are not available in the service worker.
importScripts('https://www.gstatic.com/firebasejs/8.1.0/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.1.0/firebase-messaging.js');

// Initialize the Firebase app in the service worker by passing in
// your app's Firebase config object.
// https://firebase.google.com/docs/web/setup#config-object
firebase.initializeApp({
    apiKey: "AIzaSyC34S_qkSaiKYnd_ROLcJQlAFZ-zxyhJR8",
    authDomain: "ngoc-thai-a941b.firebaseapp.com",
    databaseURL: "https://ngoc-thai-a941b.firebaseio.com",
    projectId: "ngoc-thai-a941b",
    storageBucket: "ngoc-thai-a941b.appspot.com",
    messagingSenderId: "242358485222",
    appId: "1:242358485222:web:904067178b1de4050c7b27",
    measurementId: "G-Y81F6ELZWL"
});

// Retrieve an instance of Firebase Messaging so that it can handle background
// messages.
const messaging = firebase.messaging();
