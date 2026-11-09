import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { compile } from '@tailwindcss/node';
import fs from 'node:fs';
import path from 'node:path';

function getAllTemplateFiles(dir) {
    let results = [];
    if (!fs.existsSync(dir)) {
        return results;
    }
    const list = fs.readdirSync(dir);
    list.forEach(file => {
        const filePath = path.join(dir, file);
        const stat = fs.statSync(filePath);
        if (stat && stat.isDirectory()) {
            results = results.concat(getAllTemplateFiles(filePath));
        } else if (file.endsWith('.blade.php') || file.endsWith('.js') || file.endsWith('.html')) {
            results.push(filePath);
        }
    });
    return results;
}

function tailwindFastPlugin() {
    return {
        name: 'tailwind-fast-plugin',
        enforce: 'pre',
        async transform(code, id) {
            if (id.includes('resources/css/app.css') || id.includes('resources\\css\\app.css')) {
                const files = getAllTemplateFiles('resources/views');
                const candidates = new Set();
                files.forEach(f => {
                    this.addWatchFile(f);
                    const content = fs.readFileSync(f, 'utf-8');
                    const words = content.match(/[\w\-:\[\]\/\.%\#]+/g) || [];
                    words.forEach(w => candidates.add(w));
                });

                const compiler = await compile(code, {
                    base: path.dirname(id),
                    onDependency: dep => this.addWatchFile(dep),
                });

                const compiledCss = compiler.build(Array.from(candidates));

                return {
                    code: compiledCss,
                    map: null,
                };
            }
        },
    };
}

export default defineConfig({
    plugins: [
        tailwindFastPlugin(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
