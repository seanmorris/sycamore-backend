import { View }   from 'curvature/base/View';
import { Circle } from './Circle';

export class Loading extends View
{
	template = `<div class = "loading">[[spinner]]</loading>`;

	constructor(args,parent)
	{
		super(args, parent);

		this.args.spinner = new Circle;
	}
}
