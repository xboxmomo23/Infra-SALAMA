<?php
// Activez l'affichage des erreurs pour le débogage en local
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Chargement de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Inclure l'autoloader de Composer
require dirname(__DIR__) . '/vendor/autoload.php';

// Configuration
$recipient_email = "nadirkadri099@gmail.com"; // Votre email
$recipient_name = "Infra Salama"; // Votre nom ou celui de votre entreprise

// Variables pour les messages
$success = false;
$message = '';

// Fonction pour envoyer un email avec PHPMailer
function sendContactEmail($to, $to_name, $subject, $html_content, $text_content, $from_email, $from_name)
{
    // Créer une nouvelle instance de PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Configuration du serveur
        // En environnement de développement, nous utilisons le mode debug
        if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1') {
            // Mode local - sauvegarde dans un fichier au lieu d'envoyer
            $contacts_dir = dirname(__DIR__) . '/contacts';
            if (!file_exists($contacts_dir)) {
                mkdir($contacts_dir, 0777, true);
            }

            $contact_file = $contacts_dir . '/' . date('Y-m-d_H-i-s') . '_' . $from_name . '.html';

            // Créer le fichier de contact
            if (file_put_contents($contact_file, $html_content)) {
                // Créer un fichier d'index pour lister tous les messages
                createContactsIndex($contacts_dir);
                return true;
            } else {
                return false;
            }
        }
        // En production, nous utilisons le serveur SMTP de l'hébergeur
        else {
            // Mode production - envoi par SMTP
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

// Fonction pour créer un index des contacts
function createContactsIndex($contacts_dir)
{
    $index_file = $contacts_dir . '/index.php';
    $index_content = '<?php
    // Liste des messages de contact
    $files = glob("*.html");
    rsort($files); // Plus récents en premier
    
    echo "<h1>Liste des messages de contact</h1>";
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
    $name = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name'])) : '';
    $email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
    $phone = isset($_POST['phone']) ? htmlspecialchars(trim($_POST['phone'])) : '';
    $subject = isset($_POST['subject']) ? htmlspecialchars(trim($_POST['subject'])) : '';
    $messageContent = isset($_POST['message']) ? htmlspecialchars(trim($_POST['message'])) : '';

    // Validation simple
    if (empty($name) || empty($email) || empty($subject) || empty($messageContent)) {
        $message = "Veuillez remplir tous les champs obligatoires.";
    } else {
        // Préparer le sujet de l'email
        $email_subject = "Contact du site web: $subject";

        // Version HTML de l'email pour un meilleur formatage
        $email_content_html = "
        <html>
        <head>
            <title>Nouveau message de contact</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                h2 { color: #0056b3; border-bottom: 1px solid #eee; padding-bottom: 10px; }
                .info-block { margin-bottom: 20px; }
                .label { font-weight: bold; }
                .message-block { background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>Nouveau message de contact</h2>
                
                <div class='info-block'>
                    <p><span class='label'>Nom:</span> $name</p>
                    <p><span class='label'>Email:</span> $email</p>
                    <p><span class='label'>Téléphone:</span> $phone</p>
                    <p><span class='label'>Sujet:</span> $subject</p>
                </div>
                
                <div class='message-block'>
                    <p><span class='label'>Message:</span></p>
                    <p>" . nl2br($messageContent) . "</p>
                </div>
            </div>
        </body>
        </html>
        ";

        // Version texte pour les clients mail qui ne supportent pas le HTML
        $email_content_text = "Nouveau message de contact\n\n";
        $email_content_text .= "Nom: $name\n";
        $email_content_text .= "Email: $email\n";
        $email_content_text .= "Téléphone: $phone\n";
        $email_content_text .= "Sujet: $subject\n\n";
        $email_content_text .= "Message:\n$messageContent\n";

        // Envoyer l'email ou enregistrer le message
        if (sendContactEmail($recipient_email, $recipient_name, $email_subject, $email_content_html, $email_content_text, $email, $name)) {
            $success = true;
            $message = "Votre message a été envoyé avec succès. Nous vous contacterons bientôt.";
        } else {
            $message = "Erreur lors de l'envoi de votre message. Veuillez réessayer plus tard.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traitement du message - Infra Salama</title>
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
                    <p>Veuillez utiliser le formulaire de contact.</p>
                </div>
            <?php endif; ?>

            <div class="mt-4">
                <a href="../contact.html" class="btn btn-primary">Retour au formulaire</a>
            </div>
        </div>
    </div>
</body>

</html>