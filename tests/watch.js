function watch() {
    fetch('http://127.0.0.1:8888',{
        mode: 'cors',
        headers :{
            'Content-Type' : 'text/plain',
        },
        cache : 'no-cache',
    })
        .then(function (response) {
            return response.text();
        }).then(function (html) {
            const page = document.getElementsByTagName('html')[0]
            page.innerHTML = html;
    })
}
setInterval(watch, 1000);
