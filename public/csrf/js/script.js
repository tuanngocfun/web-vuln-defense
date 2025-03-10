const clickMeButton = document.getElementById("click-me-btn");
const targetUrlInput = document.getElementById("target-url");
const requestCountInput = document.getElementById("request-count");
const outputElement = document.getElementById("output");

async function sendRequest(url, method) {
  const response = await fetch(url, {
    method,
    credentials: "include",
    body: JSON.stringify({
      amount: 25000,
      receiverId: 7,
    }),
  });

  let msg;
  if (isJson(response)) {
    msg = await response.json();
  } else {
    msg = await response.text();
  }
  outputElement.innerText = JSON.stringify(msg);
}

async function sendRequestInChunk(url, method, requestCount, chunkSize = 100) {
  const startTime = performance.now();

  let successCount = 0;
  let failedCount = 0;
  let i = 0;
  while (i < requestCount) {
    const chunk = [];
    while (i < requestCount && chunk.length < chunkSize) {
      const fetchRequest = (async function (currentIdx) {
        await fetch(url, { method, credentials: "include" }).then(
          (response) => {
            if (response.ok) {
              console.log(`#${currentIdx + 1}: Success`);
              successCount++;
            } else {
              console.log(`#${currentIdx + 1}: Failed`);
              failedCount++;
            }
          }
        );
      })(i);
      chunk.push(fetchRequest);
      i++;
    }
    await Promise.all(chunk);
  }

  const endTime = performance.now();

  outputElement.innerText = `
        Processing ${requestCount} request(s) took ${
    endTime - startTime
  } milliseconds
        Success: ${successCount} - Failed: ${failedCount}
    `;
}

function getTargetUrl() {
  return targetUrlInput.value ?? "http://localhost:9000/json";
}

function isJson(response) {
  const contentType = response.headers.get("content-type");
  return contentType && contentType.indexOf("application/json") !== -1;
}

const method = "POST";

clickMeButton.addEventListener(
  "click",
  async () => await sendRequest("http://localhost:9000/test/exchange", method)
);
// clickMeButton.addEventListener('click', async () => {
//     const url = getTargetUrl();
//     const N = requestCountInput.value ?? 10;
//     const CHUNK_SIZE = 100;

//     console.log(`Sending ${N} request(s)...`);
//     await sendRequestInChunk(url, method, N, CHUNK_SIZE);
// });
