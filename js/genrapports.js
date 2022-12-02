/* 
 * Copyright (C) 2022 ProgiSeize <contact@progiseize.fr>
 *
 * This program and files/directory inner it is free software: you can 
 * redistribute it and/or modify it under the terms of the 
 * GNU Affero General Public License (AGPL) as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AGPL for more details.
 *
 * You should have received a copy of the GNU AGPL
 * along with this program.  If not, see <https://www.gnu.org/licenses/agpl-3.0.html>.
 */

 jQuery(document).ready(function() {
	
	jQuery('.genrap-slct').each(function(e){
	    class_pdx = jQuery(this).data('addclass');
	    jQuery(this).select2({placeholder: 'Choisir dans la liste',containerCssClass: class_pdx,language: {noResults: function(){return "Aucun résultat";}}});
	});

	jQuery('.gen-openclose').on('click',function(){
		jQuery(this).find('.chevron').toggleClass('bottom');
		jQuery(this).parent('.pg-col').find('.pg-col-content').slideToggle(800);
	});
});