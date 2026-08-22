class Svenska {
    #loaded = false;
    #playing = false;
    #phrases = null;
    #current = 0;
    #categories = null;
    #audio = new Audio();

    #phraseDisplay = document.getElementById('phrase');
    #startDisplay = document.getElementById('start');
    #lessonDisplay = document.getElementById('lesson');
    #loadingDisplay = document.getElementById('loading');

    onInternalError(msg, url, line, col, error) {
        alert("An internal error occurred. Press OK to reload.\n\nDetails: " + msg + "\n" + error + "\n\n" + url + ":" + line + ":" + col);
        location.reload();
    }

    register() {
        let svenska = this;
        this.#startDisplay.addEventListener('click', function() { svenska.start(); });
        this.#lessonDisplay.addEventListener('click', function() { svenska.stop(); });
        this.#audio.addEventListener('ended', () => {
            svenska.#showNext();
        });
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
        this.#startDisplay.style.display = 'flex';
    }

    start() {
        this.#startDisplay.style.display = 'none';
        this.#lessonDisplay.style.display = 'flex';
        this.#playing = true;
        this.#current = 0;
        this.#showNext();
    }

    stop() {
        this.#startDisplay.style.display = 'flex';
        this.#lessonDisplay.style.display = 'none';
        this.#audio.pause();
        this.#playing = false;
    }

    #showNext() {
        if (!this.#loaded || !this.#phrases || !this.#playing) return;

        if (this.#current >= this.#phrases.length) {
            this.#current = 0;
        }
        const phrase = this.#phrases[this.#current];
        this.#current++;
        this.#phraseDisplay.innerText = phrase.translation.text;
        this.#playPhrase(phrase.translation.id);
    }

    #playPhrase(id) {
        const svenska = this;
        let url = 'data/audio.php?phrase=' + id;
        this.#request(url, function() {
            svenska.#processAudio(this.response);
        });
    }

    #processAudio(data) {
        if (!data.success) {
            alert("Failed to load audio: " + data.message);
            return;
        }

        if (!this.#playing) {
            return;
        }

        const audioBytes = atob(data.audio.audio);
        const byteArray = new Uint8Array(audioBytes.length);
        for (let i = 0; i < audioBytes.length; i++) {
            byteArray[i] = audioBytes.charCodeAt(i);
        }
        const blob = new Blob([byteArray], { type: 'audio/mpeg' });
        this.#audio.src = URL.createObjectURL(blob);
        this.#audio.currentTime = 0;
        this.#audio.play();
    }
}