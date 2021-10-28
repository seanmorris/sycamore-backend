import { View } from 'curvature/base/View';

export class Circle extends View
{
	constructor(args, parent)
	{
		super(args, parent);

		this.template         = require('./circle.svg');
		this.args.repeatCount = 'indefinite';
		this.args.color       = this.args.color || '444';
		this.args.speed       = this.args.speed ||0.333;

		this.args.bindTo('speed', v=>{
			this.args.halfSpeed = v*3;
		});
	}
}
