🚀 Brandkit Plugin

Turn GLPI into a showcase for your brand in just a few clicks.

Brandkit was created to simplify GLPI visual customization, allowing
administrators to apply their company branding without needing to edit
CSS or modify core files.

It provides a simple interface to customize logos, login page visuals,
and brand elements quickly and safely.

------------------------------------------------------------------------
Languages: 🇺🇸 English | 🇧🇷 Português (BR) | 🇪🇸 Español | 🇫🇷 Français
------------------------------------------------------------------------

📚 Table of Contents

-   Installation
-   Configuration
-   Known Issues
-   Translations
-   License

------------------------------------------------------------------------

📦 Installation

1.  Clone this repository:

git clone
https://github.com/juniormarcati/glpi-brandkit/glpi-brandkit.git

2.  Copy the plugin folder to:

glpi/plugins/

3.  Ensure the final folder name is:

brandkit

4.  In GLPI, go to:

Setup → Plugins → Install → Enable

------------------------------------------------------------------------

⚙️ Configuration

After enabling the plugin:

-   A “Brandkit” submenu will appear under Plugins
-   Configure the branding options according to your needs
-   Logos and visual elements will automatically apply across GLPI

------------------------------------------------------------------------

⚠️ Known Issues

Nginx Proxy Manager

If GLPI is running behind Nginx Proxy Manager, the plugin layout updates
may not apply correctly.

This happens because the “Block Common Exploits” security option may
block some visual resources used by the plugin.

Solution

Disable the following option in your proxy host configuration:

Block Common Exploits

After disabling it, clear your browser cache and reload the GLPI
interface.

------------------------------------------------------------------------

🌎 Translations

------------------------------------------------------------------------

🇺🇸 English

Turn GLPI into a showcase for your brand in just a few clicks.

This plugin makes it easy to customize logos and login page visuals
without editing CSS.

Installation:

-   Download or clone the repository
-   Extract it to:

glpi/plugins/

-   Ensure the folder name is:

brandkit

In GLPI:

Setup → Plugins → Install → Enable

Configuration:

-   After enabling, the “Brandkit” submenu will appear
-   Configure visual elements as desired

Known Issues:

If you use Nginx Proxy Manager, you may need to disable:

Block Common Exploits

to allow the plugin assets to load correctly.

------------------------------------------------------------------------

🇧🇷 Português (PT-BR)

Transforme o GLPI em uma vitrine da sua marca em poucos cliques.

O Brandkit facilita a personalização visual do GLPI sem precisar alterar
CSS ou arquivos internos.

Permite modificar logotipos e a página de login facilmente.

Instalação:

-   Faça download ou clone do repositório
-   Extraia em:

glpi/plugins/

-   Certifique-se que a pasta se chama:

brandkit

No GLPI:

Configurar → Plug-ins → Instalar → Habilitar

Configuração:

-   Após habilitar, aparecerá o submenu “Brandkit”
-   Configure os elementos visuais conforme desejar

Problemas conhecidos:

Se utilizar Nginx Proxy Manager, pode ser necessário desativar:

Block Common Exploits

para que os recursos do plugin sejam carregados corretamente.

------------------------------------------------------------------------

🇪🇸 Español

Convierte GLPI en una vitrina para tu marca en pocos clics.

Este plugin facilita la personalización visual de GLPI sin modificar CSS
ni archivos internos.

Permite cambiar logotipos y la página de inicio de sesión fácilmente.

Instalación:

-   Descargue o clone el repositorio
-   Extraiga en:

glpi/plugins/

-   Asegúrese de que la carpeta se llame:

brandkit

En GLPI:

Configurar → Complementos → Instalar → Habilitar

Configuración:

-   Después de habilitar, aparecerá el submenú “Brandkit”
-   Configure los elementos visuales según su preferencia

Problemas conocidos:

Si usa Nginx Proxy Manager, puede ser necesario desactivar:

Block Common Exploits

para permitir que los recursos del plugin se carguen correctamente.

------------------------------------------------------------------------

🇫🇷 Français

Transformez GLPI en une vitrine pour votre marque en quelques clics.

Ce plugin facilite la personnalisation visuelle de GLPI sans modifier le
CSS ni les fichiers internes.

Il permet de modifier facilement les logos et la page de connexion.

Installation :

-   Téléchargez ou clonez le dépôt
-   Extrayez dans :

glpi/plugins/

-   Vérifiez que le dossier s’appelle :

brandkit

Dans GLPI :

Configuration → Plugins → Installer → Activer

Configuration :

-   Après activation, le sous-menu “Brandkit” apparaît
-   Configurez les éléments visuels selon vos besoins

Problèmes connus :

Si vous utilisez Nginx Proxy Manager, il peut être nécessaire de
désactiver :

Block Common Exploits

pour permettre au plugin de charger correctement ses ressources.

------------------------------------------------------------------------

📜 License

This project is licensed under the GNU General Public License v3.0
(GPL-3.0).

------------------------------------------------------------------------

💬 Questions, suggestions or improvements? Open an Issue or submit a
Pull Request.
