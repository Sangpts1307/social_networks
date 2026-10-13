from functools import lru_cache

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    """Runtime configuration. Secrets must come from environment, never source code."""

    app_env: str = "local"
    ai_knowledge_port: int = 8090
    database_url: str = "mysql+pymysql://app:app_secret@127.0.0.1:33067/ai_knowledge_db"
    neo4j_uri: str = "bolt://127.0.0.1:7687"
    neo4j_username: str = "neo4j"
    neo4j_password: str = "neo4j_secret_change_me"

    model_config = SettingsConfigDict(env_file=".env", extra="ignore")


@lru_cache
def get_settings() -> Settings:
    return Settings()
