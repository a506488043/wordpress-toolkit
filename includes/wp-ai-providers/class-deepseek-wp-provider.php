<?php
/**
 * Saiita AI Content Toolkit — WordPress 7.0 DeepSeek AI Provider
 *
 * 注册 DeepSeek 作为 WordPress 原生 AI Client 的 Provider，
 * 使 WordPress 内置 AI 功能可调用 DeepSeek 模型。
 *
 * @since 2.0.22
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================================
// Hook 注册 — 必须在 class_exists 守卫之前，确保无论加载顺序如何，
// init 回调都能正常触发
// ============================================================

/**
 * 注册 DeepSeek Provider 到 WordPress AI Client。
 *
 * 该函数通过 add_action('init', ..., 9) 在 init 早期触发，
 * 早于 AI 插件的 register_provider_data (20) 和本插件的 API Key 注入 (25)。
 */
function saiita_deepseek_register_wp_provider()
{
    if (!class_exists('WordPress\AiClient\AiClient')) {
        return;
    }

    $registry = \WordPress\AiClient\AiClient::defaultRegistry();
    if ($registry->hasProvider('deepseek')) {
        return;
    }

    $registry->registerProvider('Saiita_DeepSeek_WP_Provider');
}
add_action('init', 'saiita_deepseek_register_wp_provider', 9);

// ============================================================
// 类定义 — 仅在 AiClient SDK 已加载时解析（需要父类和接口）
// ============================================================

// 仅在 WordPress AI Client 可用时加载
if (!class_exists('WordPress\AiClient\AiClient')) {
    return;
}

use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;
use WordPress\AiClient\Providers\ApiBasedImplementation\ListModelsApiBasedProviderAvailability;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleModelMetadataDirectory;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModel;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;

/**
 * DeepSeek AI Provider 主类
 */
class Saiita_DeepSeek_WP_Provider extends AbstractApiProvider
{
    protected static function baseUrl(): string
    {
        $base = get_option('saiita_ai_content_toolkit_ai_settings', array());
        return !empty($base['deepseek_api_base'])
            ? untrailingslashit($base['deepseek_api_base'])
            : 'https://api.deepseek.com/v1';
    }

    protected static function createModel(
        ModelMetadata $modelMetadata,
        ProviderMetadata $providerMetadata
    ): ModelInterface {
        return new Saiita_DeepSeek_WP_TextGeneration($modelMetadata, $providerMetadata);
    }

    protected static function createProviderMetadata(): ProviderMetadata
    {
        return new ProviderMetadata(
            'deepseek',
            'DeepSeek',
            ProviderTypeEnum::cloud(),
            'https://platform.deepseek.com/api_keys',
            RequestAuthenticationMethod::apiKey(),
            __('AI content generation with DeepSeek models.', 'saiita-ai-content-toolkit')
        );
    }

    protected static function createProviderAvailability(): ProviderAvailabilityInterface
    {
        return new ListModelsApiBasedProviderAvailability(
            static::modelMetadataDirectory()
        );
    }

    protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface
    {
        return new Saiita_DeepSeek_WP_Metadata();
    }
}

/**
 * DeepSeek 文本生成模型
 */
class Saiita_DeepSeek_WP_TextGeneration extends AbstractApiBasedModel implements TextGenerationModelInterface
{
    public function getRequestAuthentication(): RequestAuthenticationInterface
    {
        $auth = parent::getRequestAuthentication();
        if (!$auth instanceof ApiKeyRequestAuthentication) {
            return $auth;
        }
        return $auth;
    }

    final public function generateTextResult(array $prompt): GenerativeAiResult
    {
        $config = $this->getConfig();
        $transporter = $this->getHttpTransporter();

        $messages = [];
        $system_msg = $config->getSystemInstruction();

        foreach ($prompt as $message) {
            $role = $message->getRole()->isUser() ? 'user' : 'assistant';
            $content = '';
            foreach ($message->getParts() as $part) {
                $content .= $part->getText();
            }
            $messages[] = array(
                'role'    => $role,
                'content' => $content,
            );
        }

        $body = array(
            'model'       => $this->metadata()->getId(),
            'messages'    => $messages,
            'stream'      => false,
        );

        if ($system_msg) {
            $body['system'] = $system_msg;
        }

        $max_tokens = $config->getMaxTokens();
        if ($max_tokens !== null) {
            $body['max_tokens'] = $max_tokens;
        }

        $temp = $config->getTemperature();
        if ($temp !== null) {
            $body['temperature'] = max(0, min(2, $temp));
        }

        $top_p = $config->getTopP();
        if ($top_p !== null) {
            $body['top_p'] = max(0, min(1, $top_p));
        }

        $request = new Request(
            HttpMethodEnum::POST(),
            Saiita_DeepSeek_WP_Provider::url('chat/completions'),
            ['Content-Type' => 'application/json'],
            $body,
            $this->getRequestOptions()
        );

        $request = $this->getRequestAuthentication()->authenticateRequest($request);
        $response = $transporter->send($request);

        $data = $response->getData();
        if (!isset($data['choices'][0]['message']['content'])) {
            throw ResponseException::fromMissingData('DeepSeek', 'choices[0].message.content');
        }

        $finish = isset($data['choices'][0]['finish_reason']) && 'stop' === $data['choices'][0]['finish_reason']
            ? FinishReasonEnum::stop()
            : FinishReasonEnum::length();

        $candidates = array(new Candidate(
            new Message(MessageRoleEnum::model(), array(
                new \WordPress\AiClient\Messages\DTO\MessagePart($data['choices'][0]['message']['content'])
            )),
            $finish
        ));

        $usage = $data['usage'] ?? array();
        $tokenUsage = new TokenUsage(
            $usage['prompt_tokens'] ?? 0,
            $usage['completion_tokens'] ?? 0,
            $usage['total_tokens'] ?? 0
        );

        return new GenerativeAiResult(
            $data['id'] ?? '',
            $candidates,
            $tokenUsage,
            $this->providerMetadata(),
            $this->metadata(),
            $data
        );
    }
}

/**
 * DeepSeek 可用模型元数据目录
 */
class Saiita_DeepSeek_WP_Metadata extends AbstractOpenAiCompatibleModelMetadataDirectory
{
    protected function createRequest(HttpMethodEnum $method, string $path, array $headers = [], $data = null): Request
    {
        return new Request(
            $method,
            Saiita_DeepSeek_WP_Provider::url($path),
            $headers,
            $data
        );
    }

    public function getRequestAuthentication(): RequestAuthenticationInterface
    {
        return parent::getRequestAuthentication();
    }

    protected function parseResponseToModelMetadataList(Response $response): array
    {
        $responseData = $response->getData();

        $deepseek_caps = array(
            CapabilityEnum::textGeneration(),
            CapabilityEnum::chatHistory(),
        );

        $deepseek_options = array(
            new SupportedOption(OptionEnum::systemInstruction()),
            new SupportedOption(OptionEnum::maxTokens()),
            new SupportedOption(OptionEnum::temperature()),
            new SupportedOption(OptionEnum::topP()),
            new SupportedOption(OptionEnum::outputMimeType(), array('text/plain')),
            new SupportedOption(OptionEnum::inputModalities(), array(array(ModalityEnum::text()))),
            new SupportedOption(OptionEnum::outputModalities(), array(array(ModalityEnum::text()))),
        );

        $models = array();
        $defined_models = array(
            'deepseek-v4-flash'  => 'DeepSeek V4 Flash',
            'deepseek-v4-pro'    => 'DeepSeek V4 Pro',
            'deepseek-reasoner'  => 'DeepSeek Reasoner (R1)',
            'deepseek-coder'     => 'DeepSeek Coder',
            'deepseek-chat'      => 'DeepSeek Chat (Legacy)',
        );

        foreach ($defined_models as $id => $name) {
            $models[] = new ModelMetadata($id, $name, $deepseek_caps, $deepseek_options);
        }

        return $models;
    }
}

