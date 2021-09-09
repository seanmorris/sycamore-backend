exports.watcher = { awaitWriteFinish: true };
exports.paths   = { public: './docs', watched: ['./frontend'] };
exports.modules = { nameCleaner: path => path.replace(/^frontend\//, '') };

exports.files = {

	javascripts: {joinTo: 'app.js'}

	, stylesheets: {joinTo: 'app.css'}

}

exports.plugins = {

	babel: {
		presets:   ['@babel/preset-env']
		, plugins: ["@babel/plugin-proposal-class-properties"]
	}

	, raw: {
		pattern: /\.html$/,
		wrapper: content => `module.exports = ${JSON.stringify(content)}`
	}

}

exports.npm = {

	styles: {

		"video.js": [
			"dist/video-js.css"
		],

		"codemirror": [
			"lib/codemirror.css",
			"theme/elegant.css",
		]

	}
};


