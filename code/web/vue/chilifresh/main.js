import { createApp } from 'vue';
import { createPinia } from 'pinia';
import ChiliPacPanel from './components/ChiliPacPanel.vue';
import InitLists from './components/InitLists.vue';
import BooklistButton from './components/Booklist/BooklistButton.vue';
import BookshelfButton from './components/Bookshelf/BookshelfButton.vue';
import MyBooklistsPage from './components/Booklist/MyBooklistsPage.vue';

const pinia = createPinia();

const components = {
	'chilipac-panel': ChiliPacPanel,
	'init-lists': InitLists,
	'booklist-button': BooklistButton,
	'bookshelf-button': BookshelfButton,
	'my-booklists-page': MyBooklistsPage,
};

function mount(element, componentName, props) {
	const component = components[componentName];
	if (!component) {
		console.error('Unknown ChiliFresh component: ' + componentName);
		return null;
	}
	const app = createApp(component, props);
	app.use(pinia);
	app.mount(element);
	return app;
}

function mountAll(root) {
	(root || document).querySelectorAll('[data-chilifresh-component]').forEach(function (element) {
		if (element.dataset.chilifreshMounted) {
			return;
		}
		element.dataset.chilifreshMounted = 'true';
		var props = element.dataset.props ? JSON.parse(element.dataset.props) : {};
		mount(element, element.dataset.chilifreshComponent, props);
	});
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', function () {
		mountAll();
	});
} else {
	mountAll();
}

export { mount, mountAll };
