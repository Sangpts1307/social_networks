from functools import lru_cache

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    """Runtime configuration. Secrets must come from environment, never source code."""

    ai_gateway_key: str = ""
    learning_gateway_key: str = ""
    learning_service_url: str = "http://127.0.0.1:8083"
    openai_api_key: str = ""
    ai_model: str = "gpt-5-mini"
    ai_api_base_url: str = "https://api.openai.com/v1"
    ai_external_processing_enabled: bool = True
    ai_timeout_seconds: int = 30
    ai_max_input_chars: int = 20000
    ai_max_output_tokens: int = 2048

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
