import { __ } from '@wordpress/i18n';

export const dateToString = (date) => {
	let d = new Date(date);
	let month = '' + (d.getMonth() + 1);
	let day = '' + d.getDate();
	let year = d.getFullYear();

	if (month.length < 2) month = '0' + month;
	if (day.length < 2) day = '0' + day;

	return [year, month, day].join('-');
};

export const createTableDataFromDownload = (download, downloadButton) => {
		return {
			title: download.title.rendered,
			date: dateToString(download.date),
			actions:
				download.email_lock == true ? (
					downloadButton[0]
				) : (
					<>
						<a className="button primary">
							{__('Download', 'dlm-page-addon')}
						</a>
					</>
				),
			author: download.author,
			id: download.id,
			description: (
				<div
					dangerouslySetInnerHTML={{
						__html: download.content.rendered,
					}}
				/>
			),
			downloadCount: <span>{download.download_count}</span>
		};
};