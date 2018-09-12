$(document).ready(function() {
  // Allows the user to add a game to the database by calling the post function of the API
  $("#gamesForm").submit(function(event) {
    var form = $(this);
    event.preventDefault();
    $.ajax({
      type: "POST",
      url: "http://localhost:3000/api/games",
      data: form.serialize(), // serializes the form's elements.
      success: function(data) {
        window.location.replace("http://localhost:3000/slimClient/");
      }
    });
  });

  // Allows the user to remove a game from the table based on the game's id, done by clicking the delete button on the main page
  $( ".deletebtn" ).click(function() {
    if (window.confirm("Do you want to delete this game?")) {
      $.ajax({
        type: "DELETE",
        url: "http://localhost:3000/api/games/" + $(this).attr("data-id"),
        success: function(data) {
          window.location.reload();
        }
      });
    }
  });


  // User can edit a game within the table using the update part of the API. On a separate page/form, users can enter new information that the javascript will then update that row of the table
  $("#gameEdit").submit(function(event) {
    var form = $(this);
    event.preventDefault();
    $.ajax({
      type: "PUT",
      url: "http://localhost:3000/api/games/" + $(this).attr("data-id"),
      data: form.serialize(), // serializes the form's elements.
      success: function(data) {
        window.location.replace("http://localhost:3000/slimClient");
      }
    });
  });
});
