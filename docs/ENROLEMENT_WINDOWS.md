# Enrôler un agent Windows dans RansomShield

> Temps estimé : **5 minutes**
> Prérequis : accès au PC Windows + console SOC ouverte

---

## Vue d'ensemble

```
PC Windows (192.168.1.x)
    │
    └──► http://192.168.1.194:8081   (serveur SOC)
             │
           Laravel backend
             │
           Base de données
```

Le script d'installation est **auto-suffisant** : il installe Python, télécharge l'agent, configure le service et déclenche l'enrôlement automatiquement.

---

## Étape 1 — Préparer l'agent dans la console SOC

1. Ouvre la console SOC → **Agents** → **Ajouter un agent**
2. Renseigne le nom de la machine (ex: `poste-compta-01`)
3. Clique **Générer le token d'enrôlement**
4. Copie l'URL bootstrap qui s'affiche — elle ressemble à :
   ```
   http://192.168.1.194:8081/api/agent/bootstrap/XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX?os=windows
   ```

> Le token expire au bout de **48h**. Si le délai est dépassé, régénère-en un depuis la console.

---

## Étape 2 — Vérifier que le serveur PHP est démarré

Sur le **PC Linux** (le serveur SOC), ouvre un terminal et vérifie :

```bash
ss -tlnp | grep 8081
```

**Si rien ne s'affiche** → le serveur n'est pas démarré. Lance-le :

```bash
cd /home/sangronio/ransomshield/backend-laravel
nohup php artisan serve --host=192.168.1.194 --port=8081 > /tmp/rs-win.log 2>&1 &
```

Vérifie que c'est bon :
```bash
ss -tlnp | grep 8081
# Doit afficher : LISTEN ... 192.168.1.194:8081
```

---

## Étape 3 — Lancer le script d'installation sur le PC Windows

1. Sur le PC Windows, ouvre **PowerShell** (cherche "PowerShell" dans le menu Démarrer)
   - Pas besoin de "Exécuter en tant qu'Administrateur" — le script s'élève tout seul

2. Colle la commande suivante en remplaçant l'UUID par celui copié à l'étape 1 :

```powershell
powershell -ExecutionPolicy Bypass -Command "iwr 'http://192.168.1.194:8081/api/agent/bootstrap/XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX?os=windows' -UseBasicParsing | iex"
```

3. **Si une fenêtre UAC s'ouvre** ("Voulez-vous autoriser...") → clique **Oui**

4. Attends que le script se termine. Tu dois voir :

```
===================================================
   RansomShield -- Installation de l'agent
===================================================
  Machine : NOM-PC (192.168.1.xxx)
  UUID    : XXXXXXXX-...
  SOC API : http://192.168.1.194:8081/api

[1/6] Recherche de Python 3.10+...
[OK] Python 3.12.x
[2/6] Preparation du dossier C:\RansomShieldAgent...
[3/6] Telechargement des fichiers depuis le SOC...
[OK] Fichiers telecharges
[4/6] Ecriture du fichier .env...
[OK] .env configure
[5/6] Creation du venv et installation des dependances...
[OK] Dependances installees
[6/6] Installation de la tache planifiee...
===================================================
   Installation terminee avec succes !
===================================================
  L'agent demarre et va s'enroler dans les 30 secondes.
```

---

## Étape 4 — Vérifier l'enrôlement

**Sur le PC Windows** — vérifie que la tâche planifiée tourne :

```powershell
Get-ScheduledTask -TaskName RansomShieldAgent
# TaskState doit être : Running
```

**Sur la console SOC** → Agents : le PC Windows doit apparaître avec le statut **Enrolled** dans les 30 secondes.

---

## Dépannage

### "Python introuvable" pendant l'installation

Le script installe Python automatiquement (téléchargement ~25 MB depuis python.org). Si tu es derrière un proxy ou sans internet, installe Python manuellement :

```powershell
winget install -e --id Python.Python.3.12 --silent --accept-package-agreements
```

Puis relance le script d'installation.

---

### "Impossible de se connecter au serveur distant" (erreur réseau)

Le PC Windows ne peut pas joindre `192.168.1.194:8081`. Vérifie :

1. Les deux machines sont sur le même réseau Wi-Fi/câble ?
2. Le serveur PHP tourne bien (voir Étape 2)
3. Test rapide depuis le PC Windows :
   ```powershell
   Test-NetConnection -ComputerName 192.168.1.194 -Port 8081
   # TcpTestSucceeded doit être True
   ```

---

### L'agent s'installe mais n'apparaît pas dans la console

Lance l'agent manuellement pour voir les erreurs :

```powershell
cd C:\RansomShieldAgent
.\venv\Scripts\python.exe ransomshield_host_agent.py
```

Les logs de la tâche planifiée :
```powershell
Get-Content C:\RansomShieldAgent\agent.log -Tail 50
```

---

### Désinstaller l'agent

```powershell
Stop-ScheduledTask -TaskName RansomShieldAgent
Unregister-ScheduledTask -TaskName RansomShieldAgent -Confirm:$false
Remove-Item -Recurse -Force C:\RansomShieldAgent
```

---

## Résumé réseau

| Composant | IP | Port |
|---|---|---|
| Serveur SOC (Linux) | 192.168.1.194 | 8081 |
| PC Windows à enrôler | 192.168.1.xxx | — |
| VMs KVM | 10.20.0.x | — |

> Le port 8081 doit être **joignable depuis le réseau 192.168.1.x**. Les VMs utilisent directement `10.20.0.1:8080`.
