<h2 class='principal'>Matricular alunos no curso <i><?=$couseName?></i></h2>
<?php

displayEnrollStudentForm();

?>

<br>
<br>

<?php

if($guests !== FALSE){
	echo "<h3>Lista de Usuários que podem ser Matriculados</h3>";

	buildTableDeclaration();

	buildTableHeaders(array(
		'Nome',
		'E-mail',
		'Ações'
	));

	foreach ($guests as $user){
		echo "<tr>";
			echo "<td>";
				echo $user['name'];
			echo "</td>";
			echo "<td>";
				echo $user['email'];
			echo "</td>";
			echo "<td>";
				echo anchor("enrollment/enrollStudent/{$courseId}/{$user['id']}", "Matricular", "class='btn btn-primary'");
			echo "</td>";

		echo "</tr>";
	}

	buildTableEndDeclaration();
}


?>
