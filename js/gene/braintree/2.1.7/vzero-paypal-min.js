var vZeroPayPalButton=Class.create();vZeroPayPalButton.prototype={initialize:function(t,e,n,i,a,o){this.clientToken=t,this.storeFrontName=e,this.singleUse=n,this.locale=i,this.amount=0,this.currency=!1,this.client=!1,this.onlyVaultOnVault=o||!1},getClient:function(t){this.client!==!1?"function"==typeof t&&t(this.client):braintree.client.create({authorization:this.clientToken},function(e,n){return e?void console.log(e):(this.client=n,void t(this.client))}.bind(this))},setPricing:function(t,e){this.amount=parseFloat(t),this.currency=e},rebuildButton:function(){return!1},addPayPalButton:function(t,e,n,i){var a;if(e=e||$("braintree-paypal-button").innerHTML,n=n||"#paypal-container",i=i||!1,a="string"==typeof n?$$(n).first():n,!a)return console.warn("Unable to locate container "+n+" for PayPal button."),!1;if(i?a.insert(e):a.update(e),!a.select(">button").length)return console.warn("Unable to find valid <button /> element within container."),!1;var o=a.select(">button").first();o.addClassName("braintree-paypal-loading"),o.setAttribute("disabled","disabled"),this.attachPayPalButtonEvent(o,t)},attachPayPalButtonEvent:function(t,e){this.getClient(function(n){braintree.paypal.create({client:n},function(n,i){return n?void console.error("Error creating PayPal:",n):("function"==typeof e.onReady&&e.onReady(i),this._attachPayPalButtonEvent(t,i,e))}.bind(this))}.bind(this))},_attachPayPalButtonEvent:function(t,e,n){t&&e&&(Array.isArray(t)||(t=[t]),t.each(function(t){t.removeClassName("braintree-paypal-loading"),t.removeAttribute("disabled"),Event.stopObserving(t,"click"),Event.observe(t,"click",function(t){return Event.stop(t),"function"!=typeof n.validate?this._tokenizePayPal(e,n):n.validate()?this._tokenizePayPal(e,n):void 0}.bind(this))}.bind(this)))},_tokenizePayPal:function(t,e){var n=this._buildOptions();"object"==typeof e.tokenizeRequest&&(n=Object.extend(n,e.tokenizeRequest)),t.tokenize(n,function(t,n){return t?void("CUSTOMER"!==t.type&&console.error("Error tokenizing:",t)):void("function"==typeof e.onSuccess&&e.onSuccess(n))}.bind(this))},_buildOptions:function(){var t={displayName:this.storeFrontName,amount:this.amount,currency:this.currency,useraction:"commit",flow:this._getFlow()};return this.locale&&(t.locale=this.locale),t},_getFlow:function(){var t;return t=this.singleUse===!0?"checkout":"vault",null!==$("gene_braintree_paypal_store_in_vault")&&this.onlyVaultOnVault&&"vault"==t&&!$("gene_braintree_paypal_store_in_vault").checked&&(t="checkout"),t}};