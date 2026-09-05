class Svenska {
    #loaded = false;
    #playing = false;
    #dirty = false;
    #phrases = null;
    #playlist = [];
    #current = 0;
    #categories = null;
    #categoryPath = [];
    #shuffle = false;
    #wait = false;
    #repeat = false;
    #audio = new Audio();
    #waitTimer = null;
    #waitTime = 1000;
    #mode = 'listen';
    #ui = {};
    #fromLanguage = null;
    #toLanguage = null;
    #flashCardShown = false;

    #phraseDisplay = document.getElementById('phrase');
    #translationDisplay = document.getElementById('translation');
    #originalDisplay = document.getElementById('translation_from');
    #categoriesDisplay = document.getElementById('categories');
    #categoryListDisplay = document.getElementById('category_list');
    #categoryTitleDisplay = document.getElementById('category_title');
    #categoryBackButton = document.getElementById('button_category_back');
    #pauseButton = document.getElementById('button_pause');
    #playButton = document.getElementById('button_play');
    #nextButton = document.getElementById('button_next');
    #previousButton = document.getElementById('button_previous');
    #backButton = document.getElementById('button_back');
    #shuffleButton = document.getElementById('button_shuffle');
    #repeatButton = document.getElementById('button_repeat');
    #waitButton = document.getElementById('button_wait');
    #menuButton = document.getElementById('button_menu');
    #lessonDisplay = document.getElementById('lesson');
    #progressDisplay = document.getElementById('progress');
    #loadingDisplay = document.getElementById('loading');
    #modeDisplay = document.getElementById('modes');
    #modeListen = document.getElementById('mode_listen');
    #modeFlashcardsFrom = document.getElementById('mode_flashcards_from');
    #modeFlashcardsTo = document.getElementById('mode_flashcards_to');

    onInternalError(msg, url, line, col, error) {
        alert("An internal error occurred. Press OK to reload.\n\nDetails: " + msg + "\n" + error + "\n\n" + url + ":" + line + ":" + col);
        location.reload();
    }

    register() {
        let svenska = this;
        this.#menuButton.addEventListener('click', function() { svenska.showMenu(); });
        this.#playButton.addEventListener('click', function() { svenska.play(); });
        this.#pauseButton.addEventListener('click', function() { svenska.pause(); });
        this.#nextButton.addEventListener('click', function() { svenska.next(); });
        this.#shuffleButton.addEventListener('click', function() { svenska.toggleShuffle(); });
        this.#repeatButton.addEventListener('click', function() { svenska.toggleRepeat(); });
        this.#waitButton.addEventListener('click', function() { svenska.toggleWait(); });
        this.#backButton.addEventListener('click', function() { svenska.showModes(); });
        this.#categoryBackButton.addEventListener('click', function() { svenska.categoryUp(); });
        this.#previousButton.addEventListener('click', function() { svenska.previous(); });
        this.#translationDisplay.addEventListener('click', function() { svenska.toggle(); });
        this.#modeListen.addEventListener('click', function() { svenska.selectMode('listen'); });
        this.#modeFlashcardsFrom.addEventListener('click', function() { svenska.selectMode('flashcards_from'); });
        this.#modeFlashcardsTo.addEventListener('click', function() { svenska.selectMode('flashcards_to'); });
        document.addEventListener('keydown', function(event) { svenska.#onKeyDown(event); });
        this.#audio.addEventListener('ended', () => {
            this.#clearHighlights();

            if (this.#mode !== 'listen') return;

            if (this.#wait) {
                this.#waitTimer = setTimeout(() => {
                    svenska.checkNext();
                }, this.#waitTime);
            } else {
                svenska.checkNext();
            }
        });
    }

    #clearHighlights() {
        this.#originalDisplay.childNodes.forEach(child => {
            if (child instanceof HTMLElement) {
                child.classList.remove("active");
            }
        });
        this.#phraseDisplay.childNodes.forEach(child => {
            if (child instanceof HTMLElement) {
                child.classList.remove("active");
            }
        });
    }

    #onKeyDown(event) {
        if (event.metaKey || event.ctrlKey || event.altKey) return;

        const inLesson = this.#lessonDisplay.style.display !== 'none';
        if (!inLesson) {
            if (event.key !== 'Escape' || this.#categoriesDisplay.style.display === 'none') return;
            this.categoryUp();
        } else {
            switch (event.key) {
                case ' ':
                case 'Enter': this.toggle(); break;
                case 'ArrowRight': this.next(); break;
                case 'ArrowLeft': this.previous(); break;
                case 'Escape': this.showModes(); break;
                default: return;
            }
        }
        event.preventDefault();
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
        const parameters = {};
        let hashPieces = location.hash.substring(1).split('&');
        for (let index = 0; index < hashPieces.length; index++) {
            let hashPair = hashPieces[index].split('=');
            parameters[hashPair[0]] = hashPair[1];
        }

        let url = 'data/meta.php';
        let fromLanguage = parameters.hasOwnProperty('from') ? parameters['from'] : 'en-us';
        let toLanguage = parameters.hasOwnProperty('to') ? parameters['to'] : 'sv-se';
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
        this.#fromLanguage = data.from;
        this.#toLanguage = data.to;
        this.#loaded = true;
        this.#ui = data.ui;
        this.#addTooltips();
        this.showModes();
    }

    #addTooltips() {
        this.#menuButton.title = this.#getUIText('tooltip_menu');
        this.#pauseButton.title = this.#getUIText('tooltip_pause');
        this.#playButton.title = this.#getUIText('tooltip_play');
        this.#shuffleButton.title = this.#getUIText('tooltip_shuffle');
        this.#repeatButton.title = this.#getUIText('tooltip_repeat');
        this.#waitButton.title = this.#getUIText('tooltip_wait');
        this.#previousButton.title = this.#getUIText('tooltip_previous_track');
        this.#nextButton.title = this.#getUIText('tooltip_next_track');
        this.#backButton.title = this.#getUIText('tooltip_back_to_main_menu');
        this.#categoryBackButton.title = this.#getUIText('tooltip_back_to_main_menu');
        this.#modeListen.innerHTML = this.#getUIText('mode_listen');
        this.#modeFlashcardsFrom.innerHTML = this.#getUIText('mode_flashcards_from') +
            '<br><span class="flashcard_type">' + this.#fromLanguage.name + ' &#8594; ' + this.#toLanguage.name + '</span>';
        this.#modeFlashcardsTo.innerHTML = this.#getUIText('mode_flashcards_to') +
            '<br><span class="flashcard_type">' + this.#toLanguage.name + ' &#8594; ' + this.#fromLanguage.name + '</span>';
    }

    #getUIText(key) {
        return this.#ui.hasOwnProperty(key) ? this.#ui[key]['name'] : key;
    }

    #hideAll() {
        this.#loadingDisplay.style.display = 'none';
        this.#modeDisplay.style.display = 'none';
        this.#categoriesDisplay.style.display = 'none';
        this.#lessonDisplay.style.display = 'none';
    }

    showModes() {
        this.stop();
        this.#hideAll();
        this.#modeDisplay.style.display = 'flex';
    }

    selectMode(mode) {
        this.#mode = mode;
        this.showCategories();
    }

    showCategories() {
        this.#categoryPath = [];
        this.#showCategoryList();
    }

    showCategory(category) {
        this.#categoryPath.push(category);
        this.#showCategoryList();
    }

    categoryUp() {
        if (this.#categoryPath.length === 0) {
            this.showModes();
            return;
        }
        this.#categoryPath.pop();
        this.#showCategoryList();
    }

    // Renders the category currently at the end of the path, or the top-level
    // list of categories when the path is empty.
    #showCategoryList() {
        this.stop();

        const svenska = this;
        const parent = this.#categoryPath.length > 0 ? this.#categoryPath[this.#categoryPath.length - 1] : null;
        const categories = parent != null ? parent.children : Object.values(this.#categories);
        const categoryList = this.#categoryListDisplay;

        categoryList.innerHTML = '';
        categoryList.scrollTop = 0;
        this.#addPlayAllSelector(categoryList, parent != null
            ? function() { svenska.startAllCategory(parent); }
            : function() { svenska.startAll(); });
        categories.forEach(category => {
            this.#addCategorySelector(categoryList, category);
        });
        if (parent != null) {
            parent.phrases.forEach(phraseId => {
                let phrase = this.#phrases[phraseId];
                let phraseText = this.#upperFirst(phrase.text);
                this.#addSelector(categoryList, 'category_phrase', "&#x00BB;&#xFE0E;", phraseText, function() { svenska.startCategoryPhrase(parent, phrase.id); });
            });
        }

        this.#categoryTitleDisplay.textContent = parent != null ? parent.from_name : this.#getUIText('mode_' + this.#mode);
        this.#hideAll();
        this.#categoriesDisplay.style.display = 'flex';
    }

    #upperFirst(text) {
        return text.charAt(0).toUpperCase() + text.slice(1);
    }

    #addSelector(categoryList, className, iconContext, textContent, handler) {
        const categorySelector = document.createElement('div');
        categorySelector.className = 'category ' + className;
        const icon = document.createElement('span');
        icon.className = 'category_icon';
        icon.innerHTML = iconContext;
        const text = document.createElement('span');
        text.textContent = textContent;
        categorySelector.appendChild(icon);
        categorySelector.appendChild(text);
        categorySelector.addEventListener('click', handler);
        categoryList.appendChild(categorySelector);
    }

    #addPlayAllSelector(categoryList, handler) {
        this.#addSelector(categoryList, 'category_play', "&#x25B6;&#xFE0E;", this.#getUIText('play_all'), handler);
    }

    #addCategorySelector(categoryList, category) {
        let svenska = this;
        this.#addSelector(categoryList, 'category_group', "&#x25A4;&#xFE0E;", category.from_name, function() { svenska.showCategory(category); });
    }

    toggleShuffle() {
        this.#shuffle = !this.#shuffle;
        if (this.#shuffle) {
            this.#shuffleButton.classList.add('button_active');
            this.shuffle();
            this.#current = 0;
            this.continue();
        } else {
            this.#shuffleButton.classList.remove('button_active');
        }
    }

    toggleRepeat() {
        this.#repeat = !this.#repeat;
        if (this.#repeat) {
            this.#repeatButton.classList.add('button_active');
        } else {
            this.#repeatButton.classList.remove('button_active');
        }
    }

    toggleWait() {
        this.#wait = !this.#wait;
        if (this.#wait) {
            this.#waitButton.classList.add('button_active');
        } else {
            this.#waitButton.classList.remove('button_active');
        }
    }

    toggle() {
        if (this.#mode === 'listen') {
            if (this.#playing) {
                this.pause();
            } else {
                this.play();
            }
        } else {
            this.advanceFlashCard();
        }
    }

    startCategoryPhrase(category, phraseId) {
        this.#playlist = [];
        this.#addCategoryToPlaylist(category);
        if (this.#shuffle) {
            this.shuffle();
        }
        this.#current = 0;
        if (phraseId != null) {
            for (let i = 0; i < this.#playlist.length; i++) {
                if (this.#playlist[i].id === phraseId) {
                    this.#current = i;
                    break;
                }
            }
        }
        this.#start();
    }

    startAllCategory(category) {
        this.startCategoryPhrase(category);
    }

    #addCategoryToPlaylist(category) {
        for (let i = 0; i < category.phrases.length; i++) {
            this.#playlist.push(this.#phrases[category.phrases[i]]);
        }
        for (let i = 0; i < category.children.length; i++) {
            this.#addCategoryToPlaylist(category.children[i]);
        }
    }

    startAll() {
        this.#playlist = [];
        for (let categoryId in this.#categories) {
            let category = this.#categories[categoryId];
            this.#addCategoryToPlaylist(category);
        }
        if (this.#shuffle) {
            this.shuffle();
        }
        this.#current = 0;
        this.#start();
    }

    shuffle() {
        this.#playlist.sort(() => Math.random() - 0.5);
    }

    #start() {
        this.#hideAll();
        this.#lessonDisplay.style.display = 'flex';
        this.#playing = true;
        this.continue();
    }

    showMenu() {
        alert("Menu is WIP :)")
    }

    play() {
        if (!this.#loaded || this.#audio == null) return;

        this.#playButton.style.display = 'none';
        this.#pauseButton.style.display = '';
        this.#playing = true;
        if (this.#dirty) {
            this.#dirty = false;
            this.continue();
        } else {
            this.#audio.play();
        }
    }

    stop() {
        this.pause();
    }

    pause() {
        if (!this.#loaded || this.#audio == null) return;

        this.#playButton.style.display = '';
        this.#pauseButton.style.display = 'none';
        this.#playing = false;
        this.#flashCardShown = false;
        this.#audio.pause();
        if (this.#waitTimer != null) {
            clearTimeout(this.#waitTimer);
            this.#waitTimer = null;
        }
    }

    checkNext() {
        if (this.#repeat) {
            this.continue();
        } else {
            this.next();
        }
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
        this.#progressDisplay.textContent = (this.#current + 1) + ' / ' + this.#playlist.length;

        switch (this.#mode) {
            case 'listen':
                this.#continueListen(phrase);
                break;
            case 'flashcards_from':
                this.#continueFlashCardsFrom(phrase);
                break;
            case 'flashcards_to':
                this.#continueFlashCardsTo(phrase);
                break;
            default:
                alert("Error, invalid mode");
        }
    }

    #continueListen(phrase) {
        this.#phraseDisplay.innerHTML = this.#upperFirst(phrase.translation.text);
        this.#originalDisplay.innerText = this.#upperFirst(phrase.text);
        this.#originalDisplay.classList.remove('answer');
        this.#reveal(this.#translationDisplay);
        this.#pauseButton.style.display = '';
        this.#playButton.style.display = 'none';
        this.#waitButton.style.display = '';

        if (this.#playing) {
            this.#playPhrase(phrase);
        } else {
            this.#dirty = true;
        }
    }

    #continueFlashCardsFrom(phrase) {
        this.#phraseDisplay.innerHTML = this.#upperFirst(phrase.text);
        this.#originalDisplay.innerText = '';
        this.#originalDisplay.classList.add('answer');
        this.#reveal(this.#phraseDisplay);
        this.#pauseButton.style.display = 'none';
        this.#playButton.style.display = 'none';
        this.#waitButton.style.display = 'none';
    }

    #continueFlashCardsTo(phrase) {
        this.#phraseDisplay.innerHTML = this.#upperFirst(phrase.translation.text);
        this.#originalDisplay.innerText = '';
        this.#originalDisplay.classList.add('answer');
        this.#reveal(this.#phraseDisplay);
        this.#pauseButton.style.display = 'none';
        this.#playButton.style.display = 'none';
        this.#waitButton.style.display = 'none';

        if (this.#playing) {
            this.#playPhrase(phrase);
        } else {
            this.#dirty = true;
        }
    }

    advanceFlashCard() {
        if (this.#flashCardShown) {
            this.#flashCardShown = false;
            this.checkNext();
        } else {
            const phrase = this.#playlist[this.#current];
            this.#flashCardShown = true;
            if (this.#mode === 'flashcards_from') {
                this.#originalDisplay.innerHTML = this.#upperFirst(phrase.translation.text);
                this.#reveal(this.#originalDisplay);

                if (this.#playing) {
                    this.#playPhrase(phrase);
                } else {
                    this.#dirty = true;
                }
            } else {
                this.#originalDisplay.innerHTML = this.#upperFirst(phrase.text);
                this.#reveal(this.#originalDisplay);
            }
        }
    }

    #reveal(element) {
        element.classList.remove('reveal');
        void element.offsetWidth;
        element.classList.add('reveal');
    }

    #playPhrase(phrase) {
        this.#dirty = true;
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
        this.#dirty = false;

        const audioBytes = atob(data.audio.audio);
        const byteArray = new Uint8Array(audioBytes.length);
        for (let i = 0; i < audioBytes.length; i++) {
            byteArray[i] = audioBytes.charCodeAt(i);
        }

        this.#audio.ontimeupdate = null;
        if (data.audio.alignment != null) {
            let alignment = data.audio.alignment;

            // Group characters into words by splitting on spaces, tracking each
            // word's overall start/end time from its first/last character.
            const words = [];
            let current = { text: '', start: null, end: null };
            if (alignment.characters.length > 0) {
                alignment.characters[0] = alignment.characters[0].toUpperCase();
            }

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

            let phraseDisplay = this.#mode === 'flashcards_from' ? this.#originalDisplay : this.#phraseDisplay;
            phraseDisplay.innerHTML = '';
            const wordEls = words.map(w => {
                const span = document.createElement('span');
                span.className = 'word';
                span.textContent = w.text;
                span.dataset.start = w.start;
                span.dataset.end = w.end;
                phraseDisplay.appendChild(span);
                phraseDisplay.appendChild(document.createTextNode(' '));
                return span;
            });

            this.#audio.ontimeupdate = () => {
                const t = this.#audio.currentTime;
                wordEls.forEach(span => {
                    const isActive = t >= span.dataset.start && t <= span.dataset.end;
                    span.classList.toggle('active', isActive);
                });
                this.#waitTime = Math.ceil(t * 1000);
            };
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