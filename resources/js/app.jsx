// TODO: run "npm i react react-dom react-router-dom @headlessui/react @stripe/react-stripe-js @stripe/stripe-js @types/react @types/react-dom @vitejs/plugin-react typescript"
// TODO: run "npm i react react-dom react-router-dom @headlessui/react @stripe/react-stripe-js @stripe/stripe-js @types/react @types/react-dom @vitejs/plugin-react typescript"
import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import App from './App.tsx'; // Explicitly import .tsx file
import './bootstrap'; // Import Bootstrap JS
import '../sass/app.scss'; // Import global styles
import axios from 'axios';

axios.defaults.baseURL =
  import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000';
axios.defaults.headers.common['Accept'] = 'application/json';

const rootElement = document.getElementById('app');

if (rootElement) {
  const root = ReactDOM.createRoot(rootElement);
  root.render(
    <React.StrictMode>
      <BrowserRouter
        future={{ v7_startTransition: true, v7_relativeSplatPath: true }}
      >
        <App />
      </BrowserRouter>
    </React.StrictMode>
  );
} else {
  console.error("Could not find the 'app' element to mount React.");
}
