<?php
require 'config.php';

// Connexion Redis
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

session_start();
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $mdp = $_POST['mdp'];

    $sql = "SELECT * FROM useretuservices 
            WHERE nom_EtuServices = :nom 
            AND prenom_EtuServices = :prenom 
            AND mdp_EtuServices = :mdp";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nom' => $nom,
        ':prenom' => $prenom,
        ':mdp' => $mdp
    ]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {

        $user_id = $user['id_EtuServices'];
        $key = "user:$user_id";

        // Incrémenter compteur
        $count = $redis->incr($key);

        // Si première requête → expiration 10 minutes
        if ($count == 1) {
            $redis->expire($key, 600); // 600 secondes = 10 minutes
        }

        // Vérification limite
        if ($count > 10) {

            $ttl = $redis->ttl($key); // temps restant
            $minutes = ceil($ttl / 60);

            $message = "⛔ Vous avez dépassé 10 requêtes. Réessayez dans $minutes minutes.";

        } else {

            $message = "✅ Connexion réussie (requête $count/10)";

            // (Optionnel) stocker utilisateur en session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['nom'] = $nom;
            // --- Derniers utilisateurs ---
            $last_users_key = "last_users";
            $redis->lPush($last_users_key, $user_id);
            $redis->lTrim($last_users_key, 0, 9); // garde 10 derniers
            $last10 = $redis->lRange($last_users_key, 0, -1);

            // --- Top utilisateurs ---
            $top_users_key = "top_users";
            $redis->zIncrBy($top_users_key, 1, $user_id);
            $top3 = $redis->zRevRange($top_users_key, 0, 2, true);

            // --- Utilisateurs moins actifs ---
            $least3 = $redis->zRange($top_users_key, 0, 2, true);

            
        }

    } 
    else {
    $message = "❌ Identifiants incorrects";
}}

?>
<form method="POST">
    <label>Nom :</label><br>
    <input type="text" name="nom" required><br><br>

    <label>Prénom :</label><br>
    <input type="text" name="prenom" required><br><br>

    <label>Mot de passe :</label><br>
    <input type="password" name="mdp" required><br><br>

    <button type="submit">Se connecter</button>
</form>

<p style="color:green;">
    <?php echo $message; ?>
</p>

<?php if (!empty($last10)) : ?>
<h3>10 derniers utilisateurs :</h3>
<ul>
    <?php foreach ($last10 as $id) : ?>
        <li>User ID: <?php echo $id; ?></li>
    <?php endforeach; ?>
</ul>

<h3>Top 3 utilisateurs :</h3>
<ul>
    <?php foreach ($top3 as $id => $score) : ?>
        <li>User ID: <?php echo $id; ?> → <?php echo $score; ?> connexions</li>
    <?php endforeach; ?>
</ul>

<h3>Moins actifs :</h3>
<ul>
    <?php foreach ($least3 as $id => $score) : ?>
        <li>User ID: <?php echo $id; ?> → <?php echo $score; ?> connexions</li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

</body>
</html>