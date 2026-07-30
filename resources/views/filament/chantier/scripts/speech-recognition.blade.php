<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('speechRecognition', () => ({
            isRecording: false,
            recognition: null,
            init() {
                if ('webkitSpeechRecognition' in window) {
                    this.recognition = new webkitSpeechRecognition();
                    this.recognition.continuous = true;
                    this.recognition.interimResults = false;
                    this.recognition.lang = 'fr-FR';

                    this.recognition.onstart = () => {
                        this.isRecording = true;
                        new FilamentNotification()
                            .title('Enregistrement démarré')
                            .body('Vous pouvez parler...')
                            .success()
                            .send();
                    };

                    this.recognition.onresult = (event) => {
                        let final_transcript = '';
                        for (let i = event.resultIndex; i < event.results.length; ++i) {
                            if (event.results[i].isFinal) {
                                final_transcript += event.results[i][0].transcript;
                            }
                        }
                        
                        if (final_transcript !== '') {
                            let textarea = document.getElementById('speech-textarea');
                            if (textarea) {
                                let currentVal = textarea.value;
                                textarea.value = currentVal + (currentVal ? ' ' : '') + final_transcript;
                                textarea.dispatchEvent(new Event('input', { bubbles: true }));
                            } else {
                                let currentContent = this.$wire.get('data.content') || '';
                                let newContent = currentContent + (currentContent ? ' ' : '') + final_transcript;
                                this.$wire.set('data.content', newContent);
                            }
                        }
                    };

                    this.recognition.onerror = (event) => {
                        this.isRecording = false;
                        new FilamentNotification()
                            .title('Erreur')
                            .body('Erreur de reconnaissance vocale : ' + event.error)
                            .danger()
                            .send();
                    };

                    this.recognition.onend = () => {
                        this.isRecording = false;
                    };
                } else {
                    console.warn('Web Speech API n\'est pas supportée par ce navigateur.');
                }
            },
            toggleRecording() {
                if (!this.recognition) {
                    new FilamentNotification()
                        .title('Non supporté')
                        .body('Votre navigateur ne supporte pas la dictée vocale.')
                        .warning()
                        .send();
                    return;
                }

                if (this.isRecording) {
                    this.recognition.stop();
                    new FilamentNotification()
                        .title('Enregistrement arrêté')
                        .success()
                        .send();
                } else {
                    this.recognition.start();
                }
            }
        }));
    });
</script>
