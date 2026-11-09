(function($) {

	var newGridRowCountSp = 0;
	Grid.bind('mx_select_plus', 'display', function(cell)
	{
		var cell_obj = cell.find('select');

		if (cell.data('row-id')) {
			rowId = cell.data('row-id');
		} else {
			rowId = 'new_row_' + ++newGridRowCountSp;
		}

		var id = cell.parents('.grid-field').attr('id') + '_rows__row_id_' + rowId + '__col_id_' + cell.data('column-id') + '_';

		cell_obj.attr('id', id);
		add_new_options = cell_obj.data('no');
		allow_deselect  = cell_obj.data('deselect');

		var field_name = cell_obj.attr('name');
		var new_field_name = field_name.slice(0,-2) + '[new_field][]';

		cell.col = cell.col || {};
		var col_id = cell.data('column-id');
		if (!cell.col.id) {
			cell.col.id = col_id;
		}
		if (!cell.col.newCellHtml) {
			cell.col.newCellHtml = cell.html();
		}

		cell_obj.chosen({group_class: '#'+id, add_new_options: add_new_options , add_new: new_field_name, cell_obj: cell, allow_single_deselect: allow_deselect, callback: function() {}});
	});

})(jQuery);