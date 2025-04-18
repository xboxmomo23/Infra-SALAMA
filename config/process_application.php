<?php
// Activez l'affichage des erreurs pour le débogage en local
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Au début de votre fichier
require dirname(__DIR__) . '/vendor/autoload.php';

// Chargement de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Configuration
$recipient_email = "nadirkadri099@gmail.com"; // Votre email
$recipient_name = "Infra Salama"; // Votre nom ou celui de votre entreprise

// Variables pour les messages
$success = false;
$message = '';

// Fonction pour envoyer un email avec PHPMailer
function sendApplicationEmail($to, $to_name, $subject, $html_content, $text_content, $from_email, $from_name, $cv_path = '')
{
    // Créer une nouvelle instance de PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Configuration du serveur
        // En environnement de développement, nous utilisons le mode debug
        if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1') {
            // Mode local - sauvegarde dans un fichier au lieu d'envoyer
            $applications_dir = dirname(__DIR__) . '/applications';
            if (!file_exists($applications_dir)) {
                mkdir($applications_dir, 0777, true);
            }

            $application_file = $applications_dir . '/' . date('Y-m-d_H-i-s') . '_' . $from_name . '.html';

            // Préparer le contenu
            $file_content = $html_content;

            // Ajouter le lien vers le CV s'il a été téléchargé
            if (!empty($cv_path) && file_exists($cv_path)) {
                $relative_cv_path = 'uploads/' . basename($cv_path);
                $file_content = str_replace(
                    '</div></body></html>',
                    "<p><strong>CV:</strong> <a href='../$relative_cv_path' target='_blank'>Télécharger le CV</a></p></div></body></html>",
                    $file_content
                );
            }

            // Enregistrer le fichier
            if (file_put_contents($application_file, $file_content)) {
                // Créer un fichier d'index pour lister toutes les candidatures
                createApplicationsIndex($applications_dir);
                return true;
            } else {
                return false;
            }
        }
        // En production, nous utilisons le serveur SMTP de l'hébergeur
        else {
            // Mode production - envoi par SMTP
            // Note: Ces paramètres doivent être ajustés selon votre hébergeur
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Décommenter pour voir les messages de débogage
            $mail->isSMTP();
            $mail->Host = 'smtp.votrehebergeur.com'; // Remplacer par l'adresse SMTP de votre hébergeur
            $mail->SMTPAuth = true;
            $mail->Username = 'votre-email@votrehebergeur.com'; // Remplacer par votre email SMTP
            $mail->Password = 'votre-mot-de-passe'; // Remplacer par votre mot de passe SMTP
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // ou PHPMailer::ENCRYPTION_SMTPS
            $mail->Port = 587; // Port peut varier selon l'hébergeur (587 pour TLS, 465 pour SSL)

            // Expéditeur et destinataire
            $mail->setFrom($from_email, $from_name);
            $mail->addAddress($to, $to_name);
            $mail->addReplyTo($from_email, $from_name);

            // Contenu
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html_content;
            $mail->AltBody = $text_content;

            // Ajouter la pièce jointe si un CV a été téléchargé
            if (!empty($cv_path) && file_exists($cv_path)) {
                $mail->addAttachment($cv_path, basename($cv_path));
            }

            // Envoyer l'email
            $mail->send();
            return true;
        }
    } catch (Exception $e) {
        // Enregistrer l'erreur dans un fichier de log
        error_log("Erreur d'envoi d'email : " . $mail->ErrorInfo);
        return false;
    }
}

// Fonction pour créer un index des candidatures
function createApplicationsIndex($applications_dir)
{
    $index_file = $applications_dir . '/index.php';
    $index_content = '<?php
    // Liste des candidatures
    $files = glob("*.html");
    rsort($files); // Plus récents en premier
    
    echo "<h1>Liste des candidatures</h1>";
    echo "<ul>";
    foreach ($files as $file) {
        $name = str_replace([".html"], [""], $file);
        echo "<li><a href=\'" . $file . "\'>" . $name . "</a></li>";
    }
    echo "</ul>";
    ?>';
    file_put_contents($index_file, $index_content);
}

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer les données du formulaire
    $firstName = isset($_POST['firstName']) ? htmlspecialchars(trim($_POST['firstName'])) : '';
    $lastName = isset($_POST['lastName']) ? htmlspecialchars(trim($_POST['lastName'])) : '';
    $email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
    $phone = isset($_POST['phone']) ? htmlspecialchars(trim($_POST['phone'])) : '';
    $position = isset($_POST['position']) ? htmlspecialchars(trim($_POST['position'])) : '';
    $experience = isset($_POST['experience']) ? htmlspecialchars(trim($_POST['experience'])) : '';
    $coverLetter = isset($_POST['coverLetter']) ? htmlspecialchars(trim($_POST['coverLetter'])) : '';

    $full_name = $firstName . ' ' . $lastName;

    // Validation simple
    if (empty($firstName) || empty($lastName) || empty($email) || empty($position)) {
        $message = "Veuillez remplir tous les champs obligatoires.";
    } else {
        // Préparer le contenu de l'email
        $subject = "Candidature pour le poste: $position - $full_name";

        // Version HTML de l'email pour un meilleur formatage
        $email_content_html = "
        <html>
        <head>
            <title>Nouvelle candidature</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                h2 { color: #0056b3; border-bottom: 1px solid #eee; padding-bottom: 10px; }
                .info-block { margin-bottom: 20px; }
                .label { font-weight: bold; }
                .motivation { background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>Nouvelle candidature reçue</h2>
                
                <div class='info-block'>
                    <p><span class='label'>Poste:</span> $position</p>
                    <p><span class='label'>Nom complet:</span> $full_name</p>
                    <p><span class='label'>Email:</span> $email</p>
                    <p><span class='label'>Téléphone:</span> $phone</p>
                    <p><span class='label'>Expérience:</span> $experience</p>
                </div>
                
                <div class='motivation'>
                    <p><span class='label'>Lettre de motivation:</span></p>
                    <p>" . nl2br($coverLetter) . "</p>
                </div>
            </div>
        </body>
        </html>
        ";

        // Version texte pour les clients mail qui ne supportent pas le HTML
        $email_content_text = "Nouvelle candidature reçue\n\n";
        $email_content_text .= "Poste: $position\n";
        $email_content_text .= "Nom: $full_name\n";
        $email_content_text .= "Email: $email\n";
        $email_content_text .= "Téléphone: $phone\n";
        $email_content_text .= "Expérience: $experience\n\n";
        $email_content_text .= "Lettre de motivation:\n$coverLetter\n";

        // Traitement du CV si fourni
        $cv_path = '';
        $cv_uploaded = false;

        if (isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {
            // Vérifier si le dossier parent existe d'abord
            $parent_dir = dirname(__DIR__) . '/uploads';

            // Créer le dossier uploads s'il n'existe pas
            if (!file_exists($parent_dir)) {
                mkdir($parent_dir, 0777, true);
            }

            // Préparer le chemin pour sauvegarder le CV
            $cv_path = $parent_dir . '/' . time() . '_' . basename($_FILES['resume']['name']);

            // Sauvegarder le fichier
            if (move_uploaded_file($_FILES['resume']['tmp_name'], $cv_path)) {
                $email_content_text .= "\nCV reçu: " . basename($cv_path);
                $cv_uploaded = true;
            } else {
                $message = "Erreur lors du téléchargement du CV. Code: " . $_FILES['resume']['error'];
            }
        }

        // Envoyer l'email ou enregistrer la candidature
        if (sendApplicationEmail($recipient_email, $recipient_name, $subject, $email_content_html, $email_content_text, $email, $full_name, $cv_uploaded ? $cv_path : '')) {
            $success = true;
            $message = "Votre candidature a été envoyée avec succès. Nous vous contacterons bientôt.";
        } else {
            $message = "Erreur lors de l'envoi de votre candidature. Veuillez réessayer plus tard.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traitement de candidature - Infra Salama</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 50px 0;
        }

        .message-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .success-message {
            color: #28a745;
        }

        .error-message {
            color: #dc3545;
        }

        .btn-primary {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .btn-primary:hover {
            background-color: #004494;
            border-color: #004494;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="message-container">
            <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
                <div class="<?php echo $success ? 'success-message' : 'error-message'; ?>">
                    <h2><?php echo $success ? 'Merci !' : 'Erreur'; ?></h2>
                    <p><?php echo $message; ?></p>
                </div>

                <?php if ($success): ?>
                    <script>
                        // Redirection après 5 secondes
                        setTimeout(function() {
                            window.location.href = '../index.html';
                        }, 5000);
                    </script>
                    <p>Vous serez redirigé vers la page d'accueil dans 5 secondes...</p>
                <?php endif; ?>

            <?php else: ?>
                <div class="error-message">
                    <h2>Accès direct non autorisé</h2>
                    <p>Veuillez utiliser le formulaire de candidature.</p>
                </div>
            <?php endif; ?>

            <div class="mt-4">
                <a href="../recrutement.php" class="btn btn-primary">Retour au formulaire</a>
            </div>
        </div>
    </div>
</body>

</html>