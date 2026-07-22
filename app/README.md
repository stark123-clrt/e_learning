# E-Learning Platform

## Architecture

This project is developed with:

- Symfony 7
- MongoDB
- Doctrine ODM
- Docker

## Project Structure

```
src/
│
├── Controller
│   ├── EtudiantController.php
│   ├── FormateurController.php
│   ├── FormationController.php
│   ├── InscriptionController.php
│   └── UtilisateurController.php
│
├── Document
│   ├── Etudiant.php
│   ├── Formateur.php
│   ├── Formation.php
│   ├── Inscription.php
│   └── Utilisateur.php
│
├── Repository
│
└── Service
```

## Database

MongoDB Collections

- etudiants
- formateurs
- formations
- inscriptions
- utilisateurs

## Current Status

- Docker configured
- Symfony configured
- MongoDB connected
- Doctrine ODM configured
- MVC architecture created
- Controllers initialized
- First CRUD (Read - Etudiant) implemented