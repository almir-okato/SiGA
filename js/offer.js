$(document).ready(function(){
	$("#approve_offer_list_btn").click(function(event){
		var confirmed = confirm("Ao aprovar a lista de oferta não é possível adicionar ou retirar disciplinas.\n\n \
			Você poderá apenas editar o horário e local das disciplinas e o período de matrícula.");
		if(!confirmed){
			event.preventDefault();
		}
	});

	$("#enrollment_start_date").datepicker($.datepicker.regional["pt-BR"], {
		dateFormat: "dd-mm-yy"
	});

	$("#enrollment_end_date").datepicker($.datepicker.regional["pt-BR"], {
		dateFormat: "dd-mm-yy"
	});

	$("#new_enrollment_period").click(function(){
		newEnrollmentPeriod();
	});

	$("#finish_enrollment_period").click(function(){
		finishEnrollmentPeriod();
	});
	
});


function newEnrollmentPeriod(){
	
	var siteUrl = $("#site_url").val();
	var urlToPost = siteUrl + "/secretary/ajax/offerajax/newEnrollmentPeriod";
	var startDate = $('#enrollment_start_date').val();
	var endDate = $('#enrollment_end_date').val();
	var offerId = $('#offer_id').val();
	var reload = $('#reload').val();
	var oldStartDateHidden = $('#old_start_date').val();

    $.post(
		urlToPost,
		{
	        enrollment_start_date: startDate,
	        enrollment_end_date: endDate,
	        offerId: offerId,
	        old_start_date: oldStartDateHidden
		},
		function(data){
			var period = JSON.parse(data);
        	var status = period.status;
            $('#alert-msg').html(period.message);
        	if(status == true && reload == true){	
        		location.reload();
        	}
		}
	);
}