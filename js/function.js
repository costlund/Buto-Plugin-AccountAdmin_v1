function PluginAccountAdmin_v1(){
  this.account_view = function(id){
    PluginWfBootstrapjs.modal({id: 'account_view', url: '/'+app.class+'/account_view/id/'+id, lable: 'Account', size: 'xl'});
  }
  this.account_delete = function(){
    $.get('/'+app.class+'/account_delete_yes/id/'+app.account_id, function(){ 
      $('.modal').modal('hide'); 
    });
  }
}
var PluginAccountAdmin_v1 = new PluginAccountAdmin_v1();
