# TODO_REFACTORING_REQUEST_REPO.md

Ce fichier liste les occurrences de requêtes directes à la base de données en dehors des repositories, identifiées dans les dossiers `app/Http/Controllers`, `app/Livewire` et `app/Services`. L'objectif est de refactoriser ces requêtes pour les centraliser dans les repositories, afin de respecter les principes SOLID et d'améliorer la maintenabilité du code.

---

## app/Http/Controllers

### File: PushNotificationDiagnosticController.php
- L102: PushSubscription::where('subscribable_type', User::class)
- L107: PushSubscription::where('subscribable_type', User::class)

### File: PushSubscription/StorePushSubscriptionController.php
- L23: PushSubscription::updateOrCreate(

### File: PushSubscription/DestroyPushSubscriptionController.php
- L23: PushSubscription::where('subscribable_type', get_class($user))

### File: Auth/ActivateAccountViewController.php
- L37: User::where('email', $identifier)->first();

### File: WhatsApp/Webhook/SessionConnectedController.php
- L31: WhatsAppAccount::where('phone_number', $validated['phone_number'])

### File: WhatsApp/Account/IndexController.php
- L24: WhatsAppAccount::where('user_id', $user->id)

### File: Api/WhatsApp/QRScannedController.php
- L27: User::findOrFail($validated['userId']);
- L30: WhatsAppAccount::updateOrCreate(

---

## app/Livewire

### File: WhatsApp/SessionsList.php
- L43: WhatsAppAccount::where('session_name', $sessionName)

### File: WhatsApp/Dashboard.php
- L137: WhatsAppAccount::create([
- L171: WhatsAppAccount::where('user_id', Auth::id())
- L187: WhatsAppAccount::where('user_id', Auth::id())

### File: WhatsApp/CreateSession.php
- L176: WhatsAppAccount::create([

### File: WhatsApp/Conversations.php
- L28: Conversation::find($conversationId);

### File: WhatsApp/AiConfigurationForm.php
- L55: AiModel::where('is_active', true)->orderBy('name')->get();

### File: Customer/ReferralDataTable.php
- L28: User::query()

### File: Customer/InternalTransactionDataTable.php
- L30: InternalTransaction::query()->where('id', 0);
- L33: InternalTransaction::query()

### File: Customer/ExternalTransactionDataTable.php
- L32: ExternalTransaction::query()->where('id', 0);
- L35: ExternalTransaction::query()

### File: Customer/CreateCustomerRechargeForm.php
- L53: \App\Models\Geography\Country::active()->ordered()->get();

### File: Components/PhoneInput.php
- L34: Country::active()->ordered()->get();

### File: Auth/VerifyOtpForm.php
- L126: User::where('email', $this->identifier)->first();

### File: Auth/ResetPasswordForm.php
- L68: User::findByEmailOrPhone($this->identifier);

### File: Auth/LoginForm.php
- L138: User::where('email', $this->email)->first();
- L140: User::where('phone_number', $this->phone_number)->first();

### File: Auth/ActivateAccountForm.php
- L52: User::where('email', $this->identifier)->first();

### File: Customer/Ticket/TicketDataTable.php
- L38: Ticket::query()

### File: Admin/Withdrawal/ManualWithdrawalForm.php
- L83: User::find($customerId);

### File: Admin/Withdrawal/AutomaticWithdrawalForm.php
- L78: User::find($customerId);

### File: Admin/Users/UserDataTable.php
- L34: User::query()

### File: Admin/Ticket/TicketDataTable.php
- L35: Ticket::query()
- L111: User::role('admin')->orderBy('first_name')->get()->mapWithKeys(function ($user) {

### File: Admin/Ticket/AssignTicketModal.php
- L46: Ticket::findOrFail($this->ticketId);
- L47: User::findOrFail($this->selectedAdminId);
- L62: User::role('admin')->orderBy('first_name')->get();

### File: Admin/Referrals/ReferralDataTable.php
- L28: User::query()

### File: Admin/Users/Forms/EditUserForm.php
- L38: Country::active()->ordered()->get();

### File: Admin/Transactions/Forms/CreateAdminRechargeForm.php
- L51: User::find($value);

### File: Admin/Transactions/DataTables/InternalTransactionDataTable.php
- L27: InternalTransaction::query()

### File: Admin/Transactions/DataTables/ExternalTransactionDataTable.php
- L36: ExternalTransaction::query()

### File: Admin/SystemAccounts/Forms/SystemAccountWithdrawalForm.php
- L43: SystemAccount::where('type', $this->paymentMethod)->firstOrFail();
- L51: SystemAccountTransaction::create([

### File: Admin/SystemAccounts/Forms/SystemAccountRechargeForm.php
- L47: SystemAccount::where('type', $this->paymentMethod)->firstOrFail();
- L51: SystemAccountTransaction::create([

### File: Admin/SystemAccounts/DataTables/SystemAccountTransactionDataTable.php
- L27: SystemAccountTransaction::query()

---

## app/Services

### File: TicketService.php
- L32: DB::transaction(function () use ($user, $title, $description, $attachments, $priority) {
- L33: Ticket::create([
- L46: TicketMessage::create([
- L71: DB::transaction(function () use ($ticket, $user, $message, $senderType, $attachments, $isInternal) {
- L72: TicketMessage::create([
- L110: DB::transaction(function () use ($ticket, $status) {
- L125: DB::transaction(function () use ($ticket, $admin) {
- L135: DB::transaction(function () use ($ticket, $priority): Ticket {

### File: PushNotificationService.php
- L25: PushSubscription::where('subscribable_type', User::class)
- L63: Subscription::create([
- L135: PushSubscription::where('endpoint', $endpoint)->delete();

### File: CustomerDashboardMetricsService.php
- L42: ExternalTransaction::where('wallet_id', $walletId)
- L50: ExternalTransaction::where('wallet_id', $walletId)
- L58: ExternalTransaction::where('wallet_id', $walletId)

### File: BaseService.php
- L20: DB::transaction(function () use ($data) {
- L36: DB::transaction(function () use ($model, $data, $imagesIdsToDelete) {
- L64: Model::find($id);

### File: AdminDashboardMetricsService.php
- L41: User::role(UserRole::CUSTOMER()->value)
- L48: ExternalTransaction::where('transaction_type', ExternalTransactionType::WITHDRAWAL())
- L56: ExternalTransaction::where('transaction_type', ExternalTransactionType::RECHARGE())
- L64: SystemAccountTransaction::where('type', ExternalTransactionType::RECHARGE())
- L68: SystemAccountTransaction::where('type', ExternalTransactionType::WITHDRAWAL())
- L80: SystemAccount::where('is_active', true)

### File: WhatsApp/WhatsAppMessageOrchestrator.php
- L186: WhatsAppAccount::where('session_id', $sessionId)->first();

### File: WhatsApp/ContextPreparationService.php
- L27: WhatsAppAccount::findOrFail($accountMetadata->accountId);
- L38: Conversation::where('whats_app_account_id', $account->id)
- L94: Message::create([
- L141: Conversation::create([
- L192: Message::where('created_at', '<', $cutoffDate)

### File: User/UserService.php
- L34: DB::transaction(function () use (
- L44: User::create([
- L71: DB::transaction(function () use (
- L111: DB::transaction(function () use ($user) {
- L141: User::role(UserRole::CUSTOMER()->value)->get();

### File: User/UserPresenceService.php
- L30: Cache::get(self::CACHE_PREFIX.$userId);
- L52: Cache::get(self::CACHE_PREFIX.$userId);

### File: Transaction/ExternalTransactionService.php
- L26: DB::transaction(function () use ($dto) {
- L28: User::findOrFail($dto->customer_id);
- L56: ExternalTransaction::create($transactionData);
- L69: DB::transaction(function () use ($dto) {
- L71: User::findOrFail($dto->user_id);
- L79: ExternalTransaction::create([
- L101: DB::transaction(function () use ($dto) {
- L103: User::findOrFail($dto->user_id);
- L118: ExternalTransaction::create([
- L139: DB::transaction(function () use ($dto) {
- L141: User::findOrFail($dto->customer_id);
- L183: ExternalTransaction::create($transactionData);
- L196: DB::transaction(function () use ($transaction) {

### File: Customer/CustomerService.php
- L22: DB::transaction(function () use ($dto) {
- L27: User::create($userData);
- L38: Customer::create([

### File: Auth/OtpService.php
- L30: User::findByEmailOrPhone($identifier);
- L103: Cache::get($cacheKey);
- L197: User::findByEmailOrPhone($identifier);

### File: Auth/AuthenticationService.php
- L52: User::where('email', $login)->first();
- L54: User::where('phone_number', $login)->first();

### File: Auth/AccountActivationService.php
- L22: User::findByEmailOrPhone($email);
- L58: Cache::get($cacheKey);

### File: AI/PromptEnhancementService.php
- L140: AiModel::where('is_active', true)
- L155: AiModel::where('provider', 'ollama')
- L165: AiModel::where('provider', 'ollama')
- L174: AiModel::where('is_active', true)

### File: WhatsApp/AI/WhatsAppAIProcessorService.php
- L23: WhatsAppAccount::where('session_name', $sessionName)
- L95: Conversation::firstOrCreate(
- L112: Message::create([
- L153: Message::create([

### File: Payment/MyCoolPay/MyCoolPayWebhookService.php
- L73: ExternalTransaction::where('external_transaction_id', $appTransactionRef)->first();
