(function($) {

	var onDisplay = function(cell){

		var $select = $('select', cell.dom.$td),
			cell_id =  cell.field.id;
			cell_id =  cell_id.replace(/\[/g, "_");

			var id = cell_id +'_'+cell.row.id+'_'+cell.col.id+'_'+Math.floor(Math.random()*100000000);

			$select.attr('id', id);
			
			var field_name = $select.attr('name');
			var new_field_name = field_name.slice(0,-2) + '[new_field][]';

			var add_new_options = $select.data('no');
			var allow_deselect = $select.data('deselect');

			$select.chosen({add_new_options: add_new_options , add_new: new_field_name, cell_obj: cell, allow_single_deselect: allow_deselect, group_class: "."+ cell.field.id +"_" +cell.col.id, callback: function() {}});
			$('div.publish_field.publish_matrix').css({'overflow' : 'visible'});

	};

	Matrix.bind('mx_select_plus', 'display', onDisplay);

})(jQuery);
