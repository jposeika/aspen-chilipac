import { defineConfig } from 'vite';
import { resolve } from 'path';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
	plugins: [vue()],
	resolve: {
		alias: {
			'@': resolve(__dirname, 'vue/chilifresh'),
		},
	},
	define: {
		// The bundle is loaded directly via a script tag, so no downstream
		// bundler will ever substitute this for us
		'process.env.NODE_ENV': JSON.stringify('production'),
	},
	build: {
		outDir: 'interface/themes/responsive/js/lib',
		// js/lib holds other vendored/built libraries; never wipe it on build
		emptyOutDir: false,
		lib: {
			entry: 'vue/chilifresh/main.js',
			name: 'AspenChiliFresh',
			formats: ['iife'],
			fileName: () => 'chilifresh-components.min.js',
			cssFileName: 'chilifresh-components.min',
		},
	},
});
