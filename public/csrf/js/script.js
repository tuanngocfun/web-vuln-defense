const clickMeButton = document.getElementById('click-me-btn');

clickMeButton.addEventListener('click', async () => {
    const url = 'http://localhost:9000/messages';
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'include',
    });

    let msg;
    if (isJson(response)) {
        msg = await response.json();
    }
    else {
        msg = await response.text();
    }
    console.log(JSON.stringify(msg));
})

function isJson(response) {
    const contentType = response.headers.get("content-type");
    return contentType && contentType.indexOf("application/json") !== -1
}