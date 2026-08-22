class Svenska {
    #loaded = false;
    #playing = false;
    #phrases = null;
    #current = 0;
    #categories = null;
    #audio = new Audio();

    #phraseDisplay = document.getElementById('phrase');
    #originalDisplay = document.getElementById('original');
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
        this.#showNext();
    }

    stop() {
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

        this.#phraseDisplay.innerHTML = phrase.translation.text;
        this.#originalDisplay.innerText = phrase.text;
        this.#playPhrase(phrase);
    }

    #playPhrase(phrase) {
        const svenska = this;
        let url = 'data/audio.php?phrase=' + phrase.translation.id;
        this.#request(url, function() {
            svenska.#processAudio(this.response, phrase);
        });
    }

    #processAudio(data, phrase) {
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

        if (data.audio.alignment != null) {
            let alignment = data.audio.alignment;

            // Group characters into words by splitting on spaces, tracking each
            // word's overall start/end time from its first/last character.
            const words = [];
            let current = { text: '', start: null, end: null };

            alignment.characters.forEach((ch, i) => {
                if (ch === ' ') {
                    if (current.text) words.push(current);
                    current = { text: '', start: null, end: null };
                } else {
                    if (current.start === null) {
                        current.start = alignment.character_start_times_seconds[i];
                    }
                    current.end = alignment.character_end_times_seconds[i];
                    current.text += ch;
                }
            });
            if (current.text) {
                words.push(current);
            }

            this.#phraseDisplay.innerHTML = '';
            const wordEls = words.map(w => {
                const span = document.createElement('span');
                span.className = 'word';
                span.textContent = w.text;
                span.dataset.start = w.start;
                span.dataset.end = w.end;
                this.#phraseDisplay.appendChild(span);
                this.#phraseDisplay.appendChild(document.createTextNode(' '));
                return span;
            });

            this.#audio.addEventListener('timeupdate', () => {
                const t = this.#audio.currentTime;
                wordEls.forEach(span => {
                    const isActive = t >= span.dataset.start && t <= span.dataset.end;
                    span.classList.toggle('active', isActive);
                });
            });
        }

        if ('mediaSession' in navigator) {
            navigator.mediaSession.metadata = new MediaMetadata({
                title: phrase.text,
                artist: 'Swedish Phrases',
            });
            navigator.mediaSession.setActionHandler('play', () => audio.play());
            navigator.mediaSession.setActionHandler('pause', () => audio.pause());
        }

        const blob = new Blob([byteArray], { type: 'audio/mpeg' });
        if (this.#audio.src != null) {
            URL.revokeObjectURL(this.#audio.src);
        }
        this.#audio.src = URL.createObjectURL(blob);
        this.#audio.currentTime = 0;
        this.#audio.play();
    }
}