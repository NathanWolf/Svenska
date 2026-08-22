class Svenska {
    #loaded = false;
    #phrases = null;
    #current = 0;
    #categories = null;

    #phraseDisplay = document.getElementById('phrase');
    #lessonDisplay = document.getElementById('lesson');
    #loadingDisplay = document.getElementById('loading');

    onInternalError(msg, url, line, col, error) {
        alert("An internal error occurred. Press OK to reload.\n\nDetails: " + msg + "\n\n" + url + ":" + line + ":" + col);
        location.reload();
    }

    register() {
        let svenska = this;
        this.#phraseDisplay.addEventListener('click', function() { svenska.showNext(); });
    }

    #request(url, callback) {
        const request = new XMLHttpRequest();
        request.onload = callback;
        request.responseType = 'json';
        request.onerror = function() { alert("Failed to load data, sorry!"); };
        request.open("GET", url, true);
        request.send();
    }

    load() {
        const svenska = this;
        let url = 'data/meta.php';
        let fromLanguage = 'en-us';
        let toLanguage = 'sv-se';
        url = url + '?from=' + fromLanguage + '&to=' + toLanguage;
        this.#request(url, function() {
            svenska.#processData(this.response);
        });
    }

    #processData(data) {
        if (!data.success) {
            alert("Failed to load data: " + data.message);
            return;
        }

        this.#phrases = data.phrases;
        this.#categories = data.categories;
        this.#loaded = true;
        this.#loadingDisplay.style.display = 'none';
        this.#lessonDisplay.style.display = 'flex';

        this.showNext();
    }

    showNext() {
        if (!this.#loaded || this.#phrases.length == 0) return;

        if (this.#current >= this.#phrases.length) {
            this.#current = 0;
        }
        const phrase = this.#phrases[this.#current];
        this.#current++;
        this.#phraseDisplay.innerText = phrase.translation.text;
        // document.getElementById('category').innerHTML = this.#categories[phrase.category];
    }
}