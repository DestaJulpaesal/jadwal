import config from './tailwind.config.js';

export default {
    ...config,
    corePlugins: {
        ...config.corePlugins,
        preflight: false,
    },
    plugins: [],
};
