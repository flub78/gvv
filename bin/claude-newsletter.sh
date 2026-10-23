#!/bin/bash
# Wrapper pour lancer Claude Code CLI depuis PHP (www-data).
# claude.exe est un binaire natif ELF — pas besoin de node.
# ANTHROPIC_API_KEY doit être défini dans l'environnement appelant.
exec /home/frederic/.nvm/versions/node/v24.9.0/lib/node_modules/@anthropic-ai/claude-code/bin/claude.exe "$@"
