var path = require('path');
var webpack = require('webpack');

module.exports = {
	context: path.resolve(__dirname, 'src'),
	entry: {
		'admin/settings.js': './settings.js',
		'blocks/product-table/index.js': './blocks/product-table/index.js',
	},
	output: {
		path: path.resolve(__dirname, 'js'),
		filename: '[name]'
	},
	resolve: {
		modules: [
			path.resolve(__dirname, 'src'),
			'node_modules'
		],
		extensions: ['.js'],
		alias: {
			'@': path.resolve(__dirname, 'src'),
		}
	},
	externals: {
		wp: 'wp',
		react: 'React',
		'react-dom': 'ReactDOM',
		'@wordpress/components': ['wp', 'components'],
		'@wordpress/blocks': ['wp', 'blocks'],
		'@wordpress/i18n': ['wp', 'i18n'],
		'@wordpress/element': ['wp', 'element'],
		'@wordpress/block-editor': ['wp', 'blockEditor'],
	},
	module: {
		rules: [
			{
				test: /\.js$/,
				exclude: /node_modules/,
				use: {
					loader: 'babel-loader',
					options: {
						presets: ['@babel/preset-env', '@babel/preset-react'],
					},
				},
			},
			{
				test: /\.scss$/,
				use: [
					'style-loader',
					'css-loader',
					{
						loader: "sass-loader",
						options: {
							api: "modern",
						}
					}
				],
			},
		],
	},
};
