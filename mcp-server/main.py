"""
Carte Blanche — MCP Server (lecture seule)
Expose les données de la plateforme comme tools MCP via FastMCP.
Transport : SSE sur le port 3000.
"""

import os
from typing import Optional
import httpx
from fastmcp import FastMCP

SYMFONY_BASE_URL = os.getenv("SYMFONY_BASE_URL", "http://nginx:80")
API_BASE = f"{SYMFONY_BASE_URL}/api/internal"

mcp = FastMCP(
    name="Carte Blanche",
    instructions=(
        "Tu as accès aux données de Carte Blanche, une plateforme premium "
        "de vente aux enchères de restaurants en France. "
        "Utilise les tools disponibles pour répondre aux questions sur les restaurants, "
        "les enchères, les catégories et la FAQ."
    ),
)


def _get(path: str, params: dict | None = None) -> dict | list:
    """Appel HTTP GET vers l'API interne Symfony."""
    url = f"{API_BASE}{path}"
    with httpx.Client(timeout=10.0) as client:
        response = client.get(url, params=params)
        response.raise_for_status()
        return response.json()


@mcp.tool(
    description=(
        "Recherche des restaurants sur la plateforme Carte Blanche. "
        "Peut filtrer par texte libre, catégorie de cuisine et statut. "
        "Retourne les informations clés : nom, adresse, prix, CA, capacité, enchère."
    )
)
def search_restaurants(
    query: Optional[str] = None,
    category: Optional[str] = None,
    status: Optional[str] = None,
    limit: int = 10,
) -> list:
    """
    Args:
        query: Texte libre (nom, adresse, description)
        category: Filtre par catégorie de cuisine (ex: "italien", "japonais")
        status: Statut du restaurant : "publie", "brouillon", "en_attente", "vendu"
        limit: Nombre maximum de résultats (défaut 10, max 50)
    """
    params = {"limit": min(limit, 50)}
    if query:
        params["q"] = query
    if category:
        params["category"] = category
    if status:
        params["status"] = status
    return _get("/restaurants", params)


@mcp.tool(
    description=(
        "Récupère le détail complet d'un restaurant par son identifiant. "
        "Retourne toutes les informations : description, financières, enchère, catégories."
    )
)
def get_restaurant(id: int) -> dict:
    """
    Args:
        id: Identifiant numérique du restaurant
    """
    return _get(f"/restaurants/{id}")


@mcp.tool(
    description=(
        "Retourne la liste de toutes les catégories de cuisine disponibles sur la plateforme "
        "(ex: français, italien, japonais, végétarien…)."
    )
)
def get_categories() -> list:
    """Aucun paramètre requis."""
    return _get("/categories")


@mcp.tool(
    description=(
        "Recherche dans la FAQ de Carte Blanche. "
        "Utile pour répondre aux questions sur le fonctionnement de la plateforme, "
        "les enchères, les tickets, les remboursements, le processus vendeur."
    )
)
def search_faq(query: str) -> list:
    """
    Args:
        query: Question ou mots-clés à rechercher dans la FAQ
    """
    return _get("/faq", {"q": query})


@mcp.tool(
    description=(
        "Consulte les logs des interactions IA de la plateforme. "
        "Utile pour auditer les appels au chatbot, aux recommandations ou à la génération de descriptions."
    )
)
def get_ai_logs(
    type: Optional[str] = None,
    limit: int = 20,
) -> list:
    """
    Args:
        type: Type de log : "chatbot", "recommendation", "description", "email_personalization"
        limit: Nombre maximum de logs (défaut 20, max 100)
    """
    params = {"limit": min(limit, 100)}
    if type:
        params["type"] = type
    return _get("/ai-logs", params)


if __name__ == "__main__":
    mcp.run(transport="sse", host="0.0.0.0", port=3000)
