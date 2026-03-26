#!/bin/bash

echo "=== TEST DU VERROUILLAGE PESSIMISTE ==="
echo ""
echo "Étape 1: Connexion en tant que jmoderator"
COOKIE_JAR_J="/tmp/cookies_jmod.txt"
curl -s -c "$COOKIE_JAR_J" -X POST http://127.0.0.1:8000/login \
  -H "Content-Type: application/json" \
  -d '{"username":"jmoderator","password":"00000000"}' | jq -r '.message // .'

echo ""
echo "Étape 2: Récupérer la liste des annonces en attente (jmoderator)"
ANNONCE_ID=$(curl -s -b "$COOKIE_JAR_J" http://127.0.0.1:8000/api/moderation/pending-annonces | jq -r '.[0].id')
echo "ID de la première annonce: $ANNONCE_ID"

echo ""
echo "Étape 3: jmoderator VERROUILLE l'annonce"
curl -s -b "$COOKIE_JAR_J" -X POST "http://127.0.0.1:8000/api/moderation/annonce/$ANNONCE_ID/lock" | jq '.'

echo ""
echo "Étape 4: Connexion en tant que mmoderator"
COOKIE_JAR_M="/tmp/cookies_mmod.txt"
curl -s -c "$COOKIE_JAR_M" -X POST http://127.0.0.1:8000/login \
  -H "Content-Type: application/json" \
  -d '{"username":"mmoderator","password":"00000000"}' | jq -r '.message // .'

echo ""
echo "Étape 5: mmoderator essaie de VERROUILLER la même annonce (devrait échouer)"
curl -s -b "$COOKIE_JAR_M" -X POST "http://127.0.0.1:8000/api/moderation/annonce/$ANNONCE_ID/lock" | jq '.'

echo ""
echo "Étape 6: mmoderator essaie de VALIDER l'annonce (devrait retourner 423)"
curl -s -b "$COOKIE_JAR_M" -X POST "http://127.0.0.1:8000/api/moderation/annonces/$ANNONCE_ID/decide" \
  -H "Content-Type: application/json" \
  -d '{"action":"validate"}' | jq '.'

echo ""
echo "Étape 7: jmoderator VALIDE l'annonce (devrait réussir et déverrouiller)"
curl -s -b "$COOKIE_JAR_J" -X POST "http://127.0.0.1:8000/api/moderation/annonces/$ANNONCE_ID/decide" \
  -H "Content-Type: application/json" \
  -d '{"action":"validate"}' | jq '.'

echo ""
echo "Étape 8: Vérifier le statut du verrou (devrait être déverrouillé)"
curl -s -b "$COOKIE_JAR_J" -X GET "http://127.0.0.1:8000/api/moderation/annonce/$ANNONCE_ID/lock-status" | jq '.'

echo ""
echo "=== FIN DU TEST ==="
