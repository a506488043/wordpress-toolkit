<?php
/**
 * Saiita AI Content Toolkit — WordPress 7.0 硅基流动 AI Provider
 *
 * @since 2.0.23
 */

if (!defined('ABSPATH')) {
    exit;
}

function saiita_siliconflow_register_wp_provider()
{
    if (!class_exists('WordPress\AiClient\AiClient')) {
        return;
    }

    $registry = \WordPress\AiClient\AiClient::defaultRegistry();
    if ($registry->hasProvider('siliconflow')) {
        return;
    }

    $registry->registerProvider('Saiita_SiliconFlow_WP_Provider');
}
add_action('init', 'saiita_siliconflow_register_wp_provider', 9);

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

class Saiita_SiliconFlow_WP_Provider extends AbstractApiProvider
{
    protected static function baseUrl(): string
    {
        $base = get_option('saiita_ai_content_toolkit_ai_settings', array());
        return !empty($base['siliconflow_api_base'])
            ? untrailingslashit($base['siliconflow_api_base'])
            : 'https://api.siliconflow.cn/v1';
    }

    protected static function createModel(
        ModelMetadata $modelMetadata,
        ProviderMetadata $providerMetadata
    ): ModelInterface {
        return new Saiita_SiliconFlow_WP_TextGeneration($modelMetadata, $providerMetadata);
    }

    protected static function createProviderMetadata(): ProviderMetadata
    {
        return new ProviderMetadata(
            'siliconflow',
            'SiliconFlow',
            ProviderTypeEnum::cloud(),
            'https://cloud.siliconflow.cn/account/ak',
            RequestAuthenticationMethod::apiKey(),
            __('AI content generation with SiliconFlow models.', 'saiita-ai-content-toolkit')
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
        return new Saiita_SiliconFlow_WP_Metadata();
    }
}

class Saiita_SiliconFlow_WP_TextGeneration extends AbstractApiBasedModel implements TextGenerationModelInterface
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
            Saiita_SiliconFlow_WP_Provider::url('chat/completions'),
            ['Content-Type' => 'application/json'],
            $body,
            $this->getRequestOptions()
        );

        $request = $this->getRequestAuthentication()->authenticateRequest($request);
        $response = $transporter->send($request);

        $data = $response->getData();
        if (!isset($data['choices'][0]['message']['content'])) {
            throw ResponseException::fromMissingData('SiliconFlow', 'choices[0].message.content');
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

class Saiita_SiliconFlow_WP_Metadata extends AbstractOpenAiCompatibleModelMetadataDirectory
{
    protected function createRequest(HttpMethodEnum $method, string $path, array $headers = [], $data = null): Request
    {
        return new Request(
            $method,
            Saiita_SiliconFlow_WP_Provider::url($path),
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
        $caps = array(
            CapabilityEnum::textGeneration(),
            CapabilityEnum::chatHistory(),
        );

        $options = array(
            new SupportedOption(OptionEnum::systemInstruction()),
            new SupportedOption(OptionEnum::maxTokens()),
            new SupportedOption(OptionEnum::temperature()),
            new SupportedOption(OptionEnum::topP()),
            new SupportedOption(OptionEnum::outputMimeType(), array('text/plain')),
            new SupportedOption(OptionEnum::inputModalities(), array(array(ModalityEnum::text()))),
            new SupportedOption(OptionEnum::outputModalities(), array(array(ModalityEnum::text()))),
        );

        $defined_models = array(
            'deepseek-ai/DeepSeek-V3.2'  => 'DeepSeek V3.2',
            'deepseek-ai/DeepSeek-R1'    => 'DeepSeek R1',
            'deepseek-ai/DeepSeek-V3'     => 'DeepSeek V3',
            'Qwen/Qwen2.5-72B-Instruct'   => 'Qwen 2.5 72B',
            'Qwen/Qwen2.5-32B-Instruct'   => 'Qwen 2.5 32B',
            'Qwen/Qwen2.5-14B-Instruct'   => 'Qwen 2.5 14B',
            'Qwen/Qwen2.5-7B-Instruct'    => 'Qwen 2.5 7B',
            'meta-llama/Meta-Llama-3.1-405B-Instruct' => 'Llama 3.1 405B',
            'meta-llama/Meta-Llama-3.1-70B-Instruct'   => 'Llama 3.1 70B',
            'Pro/THUDM/GLM-4-9B-Chat'    => 'GLM-4 9B',
        );

        $models = array();
        foreach ($defined_models as $id => $name) {
            $models[] = new ModelMetadata($id, $name, $caps, $options);
        }

        return $models;
    }
}
