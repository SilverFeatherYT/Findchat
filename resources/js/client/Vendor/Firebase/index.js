// Import the functions you need from the SDKs you need
import { initializeApp } from 'firebase/app';
import { getFirestore } from 'firebase/firestore';
import { getAuth, GoogleAuthProvider } from 'firebase/auth';
import { getAnalytics } from 'firebase/analytics';

// Your web app's Firebase configuration
const firebaseConfig = {
    apiKey: 'AIzaSyBfHwxRVGzny1hiA1t2B_Vga8ulLIq8B80',
    authDomain: 'findhouse-dev.firebaseapp.com',
    projectId: 'findhouse-dev',
    storageBucket: 'findhouse-dev.appspot.com',
    messagingSenderId: '1078281682578',
    appId: '1:1078281682578:web:2ce458e65c9b7ae308b04b',
    measurementId: 'G-S74TD79P74'
};

// Initialize Firebase
const firebaseApp = initializeApp(firebaseConfig);
const db = getFirestore(firebaseApp);
const auth = getAuth(firebaseApp);
const analytics = getAnalytics(firebaseApp);
const googleAuthProvider = new GoogleAuthProvider();

export { firebaseApp, db, auth, analytics, googleAuthProvider };
