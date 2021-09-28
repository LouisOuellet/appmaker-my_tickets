API.Plugins.my_tickets = {
	element:{
		table:{
			index:{},
		},
	},
	forms:{
		create:{
			0:"priority",
			description:{
				0:"title",
				1:"content",
			},
			extra:{
				0:"category",
				1:"sub_category",
			},
		},
		update:{
			0:"category",
			1:"sub_category",
		},
	},
	init:function(){
		API.GUI.Sidebar.Nav.add('my_tickets', 'help');
	},
	load:{
		index:function(){
			API.Builder.card($('#pagecontent'),{ title: 'my_tickets', icon: 'my_tickets'}, function(card){
				API.request('my_tickets','read',{
					data:{client:API.Contents.Auth.User.client},
				},function(result) {
					var dataset = JSON.parse(result);
					if(dataset.success != undefined){
						for(const [key, value] of Object.entries(dataset.output.results)){ API.Helper.set(API.Contents,['data','dom','tickets',value.id],value); }
						for(const [key, value] of Object.entries(dataset.output.raw)){ API.Helper.set(API.Contents,['data','raw','tickets',value.id],value); }
						API.Builder.table(card.children('.card-body'), dataset.output.results, {
							headers:dataset.output.headers,
							id:'my_ticketsIndex',
							modal:true,
							key:'id',
							set:{
								status:1,
								priority:1,
								user:API.Contents.Auth.raw.User.id,
								email:API.Contents.Auth.raw.User.email,
								client:API.Contents.Auth.raw.User.client,
								phone:API.Contents.Auth.raw.User.phone,
							},
							plugin:"tickets",
							import:{ key:'id', },
							clickable:{ enable:true, plugin:'tickets', view:'details'},
							controls:{ toolbar:true}
						},function(response){
							API.Plugins.my_tickets.element.table.index = response.table;
						});
					}
				});
			});
		},
	},
}

API.Plugins.my_tickets.init();
