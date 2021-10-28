import { View } from 'curvature/base/View';
import { Mixin } from 'curvature/base/Mixin';
import { PromiseMixin } from 'curvature/mixin/PromiseMixin';

export class AccessPrompt extends Mixin.from(View, PromiseMixin)
{
	template = require('./access-prompt.html');

	clickAllow(event) { this[PromiseMixin.Accept](event); }
	clickDeny(event) { this[PromiseMixin.Reject](event); }
}
