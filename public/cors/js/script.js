const urlInput = document.getElementById('url');
const fetchButton = document.getElementById('fetch');
const fetchWithBodyButton = document.getElementById('fetch-with-body');
const methodInput = document.getElementById('method');
const usernameInput = document.getElementById('username');
const passwordInput = document.getElementById('password');
const fileForm = document.getElementById('file-form');
const myFileInput = document.getElementById('my-file');
const outputElement = document.getElementById('output');

const CSRF_TOKEN_KEY = 'csrf-token';

fetchButton.addEventListener('click', async (e) => {
    e.preventDefault();
    await doFetch();
});

fetchWithBodyButton.addEventListener('click', async (e) => {
    const body = JSON.stringify({ username: usernameInput.value, password: passwordInput.value }) ;
    e.preventDefault();
    await doFetch({
        body
    });
});

fileForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(fileForm);
    try {
        const response = await fetch(getUrl(), {
            method: 'POST',
            body: formData
        });

        if (isJson(response)) {
            await handleJson(response);
        }
        else if (isBlob(response)) {
            await handleBlob(response);
        }
        else {
            const text = await response.text();
            outputElement.innerText = text;
        }
    }
    catch (error) {
        outputElement.innerText = error.message;
    }
});

let isLogging = false;
let isRefreshing = false;
let isLogout = false;

async function doFetch(options = {}) {
    const url = getUrl();
    isLogging = url === 'http://localhost:9000/auth/login';
    isRefreshing = url === 'http://localhost:9000/auth/token';
    isLogout = url === 'http://localhost:9000/auth/logout';

    const finalOptions = {
        ...options,
        headers: {
            ...options.headers
        }
    };

    const csrfToken = getCsrfToken();
    if (csrfToken) {
        finalOptions.headers['X-XSRF-Token'] = csrfToken;
    }

    try {
        const fetchOptions = {
            method: methodInput.value,
            credentials: 'include',
            ...finalOptions
        };
        const response = await fetch(url, fetchOptions);
        
        if (!response.ok) {
            outputElement.innerHTML = await response.text();
            return;
        }

             
        if (isLogging) {
            outputElement.innerText = 'Login Successfully';
        }
        if (isLogout) {
            outputElement.innerText = 'Logout Successfully';
        }       
        else if (isRefreshing) {
            outputElement.innerText = 'Refresh Successfully';
        }

        if (isJson(response)) {
            await handleJson(response);
        }
        else if (isBlob(response)) {
            await handleBlob(response);
        }
        else if (response.status !== 204) {
            const html = await response.text();
            outputElement.innerHTML = html;
        }
    } 
    catch (error) {
        outputElement.innerText = error.message;
    }
}

function getUrl() {
    return urlInput.value.trim().toLowerCase();
}

function isJson(response) {
    const contentType = response.headers.get("content-type");
    return contentType && contentType.indexOf("application/json") !== -1;
}

function isBlob(response) {
    const disposition = response.headers.get('Content-Disposition');
    return disposition;
}

async function handleJson(response) {
    const data = await response.json();
    if (isLogging || isRefreshing) {
        const csrfToken = data.csrf;
        storeCsrfToken(csrfToken);
    }

    const isSet = isLogging || isLogout || isRefreshing;
    if (!isSet) {
        outputElement.innerText = JSON.stringify(data);
    }
}

async function handleBlob(response) {
    const disposition = response.headers.get('Content-Disposition');
    const filename = disposition?.includes('attachment')
        ? disposition.split('filename=')[1]?.replace(/"/g, '') || (new Date()).toString()
        : null;

    const blob = await response.blob();
    const url = URL.createObjectURL(blob);
    if (!filename) {
        // Open in a new tab for inline display
        open(url, '_blank');
    } 
    else {
        // Trigger a download for attachments
        const a = document.createElement('a');
        a.href = url;
        a.download = filename; // Use the extracted filename or a default
        outputElement.appendChild(a);
        a.click();
        outputElement.removeChild(a);
    }
    URL.revokeObjectURL(url);
}

function getCsrfToken() {
    return localStorage.getItem(CSRF_TOKEN_KEY);
}

function storeCsrfToken(token) {
    localStorage.setItem(CSRF_TOKEN_KEY, token);
}

function randomString(length) {
    let result = '';
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    const charactersLength = characters.length;
    let counter = 0;
    while (counter < length) {
        result += characters.charAt(Math.floor(Math.random() * charactersLength));
        counter += 1;
    }
    return result;
}