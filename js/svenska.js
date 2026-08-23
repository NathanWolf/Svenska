class Svenska {
    #loaded = false;
    #playing = false;
    #dirty = false;
    #phrases = null;
    #playlist = [];
    #current = 0;
    #categories = null;
    #shuffle = false;
    #audio = new Audio();

    #phraseDisplay = document.getElementById('phrase');
    #translationDisplay = document.getElementById('translation');
    #originalDisplay = document.getElementById('translation_from');
    #categoriesDisplay = document.getElementById('categories');
    #pausedButton = document.getElementById('button_paused');
    #nextButton = document.getElementById('button_next');
    #previousButton = document.getElementById('button_previous');
    #backButton = document.getElementById('button_back');
    #shuffleButton = document.getElementById('button_shuffle');
    #lessonDisplay = document.getElementById('lesson');
    #loadingDisplay = document.getElementById('loading');
    #controlsDisplay = document.getElementById('controls');

    onInternalError(msg, url, line, col, error) {
        alert("An internal error occurred. Press OK to reload.\n\nDetails: " + msg + "\n" + error + "\n\n" + url + ":" + line + ":" + col);
        location.reload();
    }

    register() {
        let svenska = this;
        this.#pausedButton.addEventListener('click', function() { svenska.play(); });
        this.#nextButton.addEventListener('click', function() { svenska.next(); });
        this.#shuffleButton.addEventListener('click', function() { svenska.toggleShuffle(); });
        this.#backButton.addEventListener('click', function() { svenska.showCategories(); });
        this.#previousButton.addEventListener('click', function() { svenska.previous(); });
        this.#translationDisplay.addEventListener('click', function() { svenska.toggle(); });
        this.#audio.addEventListener('ended', () => {
            svenska.next();
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
        this.showCategories();
    }

    showCategories() {
        this.stop();

        const svenska = this;
        const categories = this.#categories;
        const categoryList = this.#categoriesDisplay;
        categoryList.innerHTML = '';

        const li = document.createElement('li');
        li.className = 'category';
        li.textContent = 'Play All';
        li.addEventListener('click', function() { svenska.startAll(); });
        categoryList.appendChild(li);

        categories.forEach(category => {
            const li = document.createElement('li');
            li.className = 'category';
            li.textContent = category.from_name;
            li.addEventListener('click', function() { svenska.startCategory(category.id); });
            categoryList.appendChild(li);
        });

        this.#lessonDisplay.style.display = 'none';
        categoryList.style.display = 'flex';
    }

    toggleShuffle() {
        if (this.#shuffle) {
            this.#shuffleButton.classList.remove('button_active');
        } else {
            this.#shuffleButton.classList.add('button_active');
            this.shuffle();
            this.#current = 0;
            this.continue();
        }
        this.#shuffle = !this.#shuffle;
    }

    toggle() {
        if (this.#playing) {
            this.pause();
        } else {
            this.play();
        }
    }

    startCategory(categoryId) {
        this.#playlist = [];
        for (let i = 0; i < this.#phrases.length; i++) {
            let phrase = this.#phrases[i];
            if (phrase.category_id === categoryId) {
                this.#playlist.push(phrase);
            }
        }
        this.#start();
    }

    startAll() {
        this.#playlist = [...this.#phrases];
        this.#start();
    }

    shuffle() {
        this.#playlist.sort(() => Math.random() - 0.5);
    }

    #start() {
        this.#lessonDisplay.style.display = 'flex';
        this.#categoriesDisplay.style.display = 'none';
        this.#playing = true;
        this.continue();
    }

    play() {
        if (!this.#loaded || this.#audio == null) return;

        this.#playing = true;
        if (this.#dirty) {
            this.#dirty = false;
            this.continue();
        } else {
            this.#audio.play();
        }
        this.#controlsDisplay.style.visibility = 'hidden';
    }

    stop() {
        if (!this.#loaded || this.#audio == null) return;

        this.#playing = false;
        this.#audio.pause();
        this.#controlsDisplay.style.visibility = 'hidden';
    }

    pause() {
        if (!this.#loaded || this.#audio == null) return;

        this.#audio.pause();
        this.#playing = false;
        this.#controlsDisplay.style.visibility = 'visible';
    }

    next() {
        this.#current++;
        if (this.#current >= this.#playlist.length) {
            this.#current = 0;
        }
        this.continue();
    }

    previous() {
        this.#current--;
        if (this.#current < 0) {
            this.#current = this.#playlist.length - 1;
        }
        this.continue();
    }

    continue() {
        if (!this.#loaded || this.#playlist.length === 0) return;

        const phrase = this.#playlist[this.#current];
        this.#phraseDisplay.innerHTML = phrase.translation.text;
        this.#originalDisplay.innerText = phrase.text;

        if (this.#playing) {
            this.#playPhrase(phrase);
        } else {
            this.#dirty = true;
        }
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

        let svenska = this;
        if ('mediaSession' in navigator) {
            navigator.mediaSession.metadata = new MediaMetadata({
                title: phrase.text,
                artist: 'Swedish Phrases',
                artwork: [{ src: 'https://svenska.elmakers.com/images/flag-sv.png' }]
            });
            navigator.mediaSession.setActionHandler('play', () => svenska.play());
            navigator.mediaSession.setActionHandler('pause', () => svenska.pause());
            navigator.mediaSession.setActionHandler('previoustrack', () => svenska.previous());
            navigator.mediaSession.setActionHandler('nexttrack', () => svenska.next());
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