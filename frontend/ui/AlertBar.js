import { View } from 'curvature/base/View';
import { Application } from '../Application';

export class AlertBar extends View
{
	template = `<div class = "alert-bar level-alert">
	<p>log into matrix to chat</p>
	<button class = "black-button" cv-on = "click:login(event)">login</button>
	<button class = "close-button" cv-on = "click:remove"></button>
</div>`;

	onAttach()
	{
		this.listen(
			Application.matrix
			, 'logged-in'
			, () => this.remove()
		);
	}

	login(event)
	{
		Application.matrix.initSso(
			location.origin
			, event.srcElement.getRootNode().defaultView
		);
	}
}
