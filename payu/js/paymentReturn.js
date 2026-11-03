$(document).ready(function () {
    var statusWaitingElement = document.getElementById("status-waiting");

    function showResult(status, message) {
        status = status.toLowerCase();
        const statusResultElement = document.getElementById(`status-result-${status}`);
        statusResultElement.style.display = 'block';
        if (message) {
            statusResultElement.innerHTML = message;
        }
        statusWaitingElement.style.display = 'none';
    }

    async function getStatus(counter) {
        const response = await fetch(window.payu_get_status_url);
        const contentType = response.headers.get('content-type');

        if (!response.ok) {
            const isJson = contentType?.includes('application/json');
            let message;

            if (!isJson) {
                message = await response.text();
            }
            else {
                const body = await response.json();
                message = body.message ?? 'Unknown Error';
            }
            showResult(message, 'ERROR');
        }
        const json = await response.json();
        let status = json.status;

        if (status === 'COMPLETED' || status === 'CANCELED') {
            showResult(status);
            return true;
        }
        else {
            if (counter === 0) {
                showResult(status);
                return true;
            }
        }
        return false;
    }

    async function run() {
        let counter = 10;
        const isFirstFinal = await getStatus(counter);

        if (!isFirstFinal) {
            const interval = setInterval(async () => {
                const isFinal = await getStatus(counter);
                if (isFinal || counter === 0) {
                    clearInterval(interval);
                }
                else {
                    counter--;
                }
            }, 4000);
        }
    }

    setTimeout(run, 3000);
});
