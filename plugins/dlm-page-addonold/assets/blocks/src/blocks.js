//import 'whatwg-fetch';
import Select from 'react-select';
import SortableList from 'react-sortable-dnd-list/commonjs/SortableList';

import { createTableDataFromDownload } from './blocks.utils';

import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps, RichText } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { Fragment } from '@wordpress/element';
import { useEffect, useState } from '@wordpress/element';

registerBlockType('dlm-page-addon/downloads-list-table', {
	apiVersion: 2,
	title: __('Downloads List', 'dlm-page-addon'),
	icon: 'download',
	keywords: [
		__('download', 'dlm-page-addon'),
		'download monitor',
		__('file', 'dlm-page-addon'),
	],
	category: 'common',
	attributes: {
		category: {
			type: 'object',
			default: { value: '', label: __('All', 'dlm-page-addon'), id: 0 },
		},
		headers: {
			type: 'array',
			default: [
				{ label: __('ID', 'dlm-page-addon'), name: 'id', show: true },
				{
					label: __('Title', 'dlm-page-addon'),
					name: 'title',
					show: true,
				},
				{
					label: __('Author', 'dlm-page-addon'),
					name: 'author',
					show: true,
				},
				{
					label: __('Description', 'dlm-page-addon'),
					name: 'description',
					show: true,
				},
				{
					label: __('Download Count', 'dlm-page-addon'),
					name: 'downloadCount',
					show: true,
				},
				{
					label: __('Date', 'dlm-page-addon'),
					name: 'date',
					show: true,
				},
				{
					label: __('Actions', 'dlm-page-addon'),
					name: 'actions',
					show: true,
				},
			],
		},
		// @todo: orderby and order for next iteration
		/* orderby: {
			type: 'object',
			default: { value: 'date', label: __('Date', 'dlm-page-addon'), id: 0 },			
		
		},
		order: {
			type: 'object',
			default: { value: 'ASC', label: __('Ascendent', 'dlm-page-addon'), id: 0 },
		} 		 */	
	},
	edit: (props) => {
		
		// @todo: orderby and order for next iteration
		const {
			attributes: { headers, category/*, orderby ,order */ },
			setAttributes,
		} = props;

		// @todo: For next iteration
		// Set our orderby and order values
		/*  const orderbyValues = [
			{ label: __('Date', 'dlm-page-addon'), value: 'date', id:0 },
			{ label: __('Menu order', 'dlm-page-addon'), value: 'menu_order', id:1 },
			{ label: __('Title', 'dlm-page-addon'), value: 'title', id:2 },
			{ label: __('Download count', 'dlm-page-addon'), value: 'download_count', id:3 }
		];
		const orderValues = [
			{ label: __('Ascendant', 'dlm-page-addon'), value: 'ASC', id:0 },
			{ label: __('Descendant', 'dlm-page-addon'), value: 'DESC', id:1 },
		]; */

		const blockProps = useBlockProps({
			className: 'dlm-page-addon-list',
		});

		const [categories, setCategories] = useState([]);
		const [downloads, setDownloads] = useState([]);

		const setHeaders = (headers) => {

			setAttributes({ headers});
		};

		const setHeadersShow = (header ) => {
			const newHeaders = [...headers];

			let foundIndex = -1;
			newHeaders.find( (item, index) => {
				if (item.name === header.name) {
					foundIndex = index;

				}
			} );

			if( foundIndex > -1 ) {
				newHeaders[foundIndex].show = !newHeaders[foundIndex].show;
			}
			setAttributes({headers: newHeaders});
		}

		const setHeadersLabel = ( index, label ) => {

			const newHeaders = [...headers];

			newHeaders[index].label = label;
			
			setAttributes({headers: newHeaders});
		}

		const DraggableItemComponent = ({
			dragging,
			dragged,
			children,
			...rest
		}) => {

			const { label, name, show } = children;
			return (
				<div
					{...rest}
					className={`list__item ${
						dragged ? 'list__item--dragged' : ''
					}`}
					style={{ display: 'flex', gap: '0.25rem' }}
				>
					<svg
						xmlns="http://www.w3.org/2000/svg"
						className="h-6 w-6"
						fill="none"
						viewBox="0 0 24 24"
						width="20"
						height="20"
						stroke="currentColor"
						data-draggable
						style={{ cursor: 'move' }}
					>
						<path
							strokeLinecap="round"
							strokeLinejoin="round"
							strokeWidth={2}
							d="M8 9l4-4 4 4m0 6l-4 4-4-4"
						/>
					</svg>
					<ToggleControl
						label={label}
						checked={show}
						onChange={() =>
							setHeadersShow(children)
						}
					/>
				</div>
			);
		};

		const SortableListPage = () => {
			return (
				<SortableList
					className="list"
					itemComponent={DraggableItemComponent}
					value={headers}
					onChange={setHeaders}
					dragHandleDataAttribute="draggable"
				/>
			);
		};

		useEffect(() => {
			fetch(`${dlmPARestUrl}wp/v2/dlm_download_category`)
				.then((res) => res.json())
				// 'terms' contains valid term objects
				.then((terms) => {
					let options = [
						{
							value: '',
							label: __('All', 'dlm-page-addon'),
							id: 0,
						},
					];
					let mappedTerms = terms.map((term) => {
						return {
							value: term.slug,
							label: term.name,
							id: term.id,
						};
					});
					options = options.concat(mappedTerms);
					setCategories(options);
				});
		}, []);
		useEffect(() => {
			fetch(`${dlmPARestUrl}wp/v2/dlm_download/`)
				.then((res) => res.json())
				.then((downloads) => {
					if (category.value != '') {
						downloads = downloads.filter((download) => {							
							return download.dlm_download_category.includes(
								category.id
							);
						});
					}
					setDownloads(downloads);
				});
		}, [category]);

		const downloadButton = [
			<>
				<a className="button primary">Maybe</a>
			</>,
		];
		{
			wp.hooks.doAction(
				'dlm_page_addon_button',
				downloadButton,
				'dlm/page-addon'
			);
		}

		return (
			<div {...blockProps}>
				<InspectorControls>
					<PanelBody title={__('Category', 'dlm-page-addon')}>
						<Select
							value={category}
							onChange={(value) => {
								setAttributes({ category: value });
							}}
							options={categories}
						/>
					</PanelBody>

					<PanelBody title={__('Table Columns', 'dlm-page-addon')}>
						<SortableListPage />
					</PanelBody>

					{/*@todo : Select works, re-render of downloads function still needs to be done*/}
					{/* <PanelBody title={__('Orderby & Order', 'dlm-page-addon')}>
						<Select
							value={orderby}
							onChange={(value) => {
								setAttributes({ orderby: value });
							}}
							options={orderbyValues}
						/>

						<Select
							value={order}
							onChange={(value) => {
								setAttributes({ order: value });
							}}
							options={orderValues}
						/>
					</PanelBody> */}
				</InspectorControls>
				<div>
					<table>
						<thead>
							<tr>
								{headers.map((header, index) => {
									if ( header.show ) {
										return (
											<th key={index}>
												<RichText
													tagName="span"
													withoutInteractiveFormatting="true"
													value={header.label}
													onChange={(value) => setHeadersLabel( index, value )}
												/>
											</th>
										);
									}
								})}
							</tr>
						</thead>
						<tbody>
							{downloads.map((download) => {
								let returnObj = createTableDataFromDownload(
									download,
									downloadButton
								);

								return (
									<tr key={download.id}>
										{headers.map((header) => {											
											if ( header.show ) {												
												return (
													<td key={download.id+'.'+header.name}>
														{returnObj[header.name]}
													</td>
												);
											}
										})}
									</tr>
								);
							})}
						</tbody>
					</table>
				</div>
			</div>
		);
	},
	save: () => null,
});
