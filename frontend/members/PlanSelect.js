import { View } from 'curvature/base/View';
import { Subscribe } from '../donate/Subscribe';

import { Application } from '../Application';

export class PlanSelect extends View
{
	template = `<div class = "plan-select" cv-each = "plans:plan">

	<div class = "plan" cv-on = "click:subscribe(event, plan)">
		<span class = "name">[[plan.name]]</span>
		<img src = "/star-circle.svg" />
		<span class = "description">[[plan.description]]</span>
		<span class = "price">
			[[plan.price]] /
			<span cv-if = "!plan.frequency" cv-is = "1">[[plan.frequency]]</span>
			month<span cv-if = "!plan.frequency" cv-is = "1">s</span>
			[[plan.currency]]
		</span>
		<button class = "black-button">subscribe</button>
	</div>

	</div>`;

	constructor(args, parent)
	{
		super(args, parent);

		fetch('/pay/plans')
		.then(r => r.json())
		.then(r => this.args.plans = r);
	}

	subscribe(event, plan)
	{
		const subscribe = new Subscribe;

		subscribe.args.plan = plan;

		Application.modalHost.add(subscribe);

		subscribe.addEventListener('modalSuccess', () => console.log(event));
	}
}
