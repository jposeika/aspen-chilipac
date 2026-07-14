import { createApp } from 'vue';
import ChiliPacPanel from './components/ChiliPacPanel.vue';

const components = {
	'chilipac-panel': ChiliPacPanel,
};

function mount(element, componentName, props) {
	const component = components[componentName];
	if (!component) {
		console.error('Unknown ChiliFresh component: ' + componentName);
		return null;
	}
	const app = createApp(component, props);
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
