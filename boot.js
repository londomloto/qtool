const support = 'import' in document.createElement('link') && 
                window.customElements !== undefined;

const app = {

    run: _ => {
        if ( ! support) {
            app.poly().then(app.init);
        } else {
            app.init();
        }

    },

    poly: _ => {
        let offset = document.querySelector('script#boot');
        let script = document.createElement('script');
        let q = {};

        q.promise = new Promise(res => {
            q.resolve = res;
        });

        script.src = 'bower_components/webcomponentsjs/webcomponents-lite.js';
        document.head.insertBefore(script, offset);

        addEventListener('WebComponentsReady', _ => {
            q.resolve();
        });

        return q.promise;
    },

    init: _ => {
        // define base
        let meta = document.querySelector('meta[name="application-base"]'); 
        
        if ( ! meta.content) {
            let base = location.pathname;

            base = base.replace(/\/$/, '') + '/';
            
            meta.content = base;
            document.querySelector('#base').href = base;
        }

        let link = document.createElement('link');

        link.href = 'src/q-tool.html';
        link.rel = 'import';

        document.head.appendChild(link);


        // install service worker?
        /*if ('serviceWorker' in navigator) {

            window.addEventListener('load', _ => {
                navigator.serviceWorker.register('/qtool/worker.js').then(
                    reg => {
                        console.log('reg scope: ' + reg.scope);
                    },
                    err => {
                        console.log('reg error: ' + err);
                    }
                );
            });

        }*/
    }

};

app.run();