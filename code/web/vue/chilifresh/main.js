import { createApp } from 'vue';
import { createPinia } from 'pinia';
import ChiliPacPanel from './components/ChiliPacPanel.vue';
import InitLists from './components/InitLists.vue';
import BooklistButton from './components/Booklist/BooklistButton.vue';
import BookshelfButton from './components/Bookshelf/BookshelfButton.vue';
import MyBooklistsPage from './components/Booklist/MyBooklistsPage.vue';
import MyBooklist from './components/Booklist/MyBooklist.vue';
import BooklistFilter from './components/Booklist/BooklistFilter.vue';
import BooklistSearchResults from './components/Booklist/BooklistSearchResults.vue';
import BooklistSearchSidebar from './components/Booklist/BooklistSearchSidebar.vue';
import MyBookshelfPage from './components/Bookshelf/MyBookshelfPage.vue';
import ConnectionsCard from './components/Connections/ConnectionsCard.vue';

const pinia = createPinia();

const components = {
	'chilipac-panel': ChiliPacPanel,
	'init-lists': InitLists,
	'booklist-button': BooklistButton,
	'bookshelf-button': BookshelfButton,
	'my-booklists-page': MyBooklistsPage,
	'my-booklist': MyBooklist,
	'booklist-filter': BooklistFilter,
	'booklist-search-results': BooklistSearchResults,
	'booklist-search-sidebar': BooklistSearchSidebar,
	'my-bookshelf-page': MyBookshelfPage,
	'connections-card': ConnectionsCard,
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
