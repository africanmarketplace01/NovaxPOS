class SourcingSummary {
  final int id;
  final String title;
  final String status;
  final String? category;
  final int responses;
  final String? imageUrl;
  final bool hasVideo;
  final String? createdAt;
  SourcingSummary({
    required this.id,
    required this.title,
    required this.status,
    this.category,
    required this.responses,
    this.imageUrl,
    this.hasVideo = false,
    this.createdAt,
  });
  factory SourcingSummary.fromJson(Map<String, dynamic> j) => SourcingSummary(
        id: j['id'] as int,
        title: (j['title'] ?? '') as String,
        status: (j['status'] ?? 'open') as String,
        category: j['category'] as String?,
        responses: (j['responses'] ?? 0) as int,
        imageUrl: j['image_url'] as String?,
        hasVideo: (j['has_video'] ?? false) as bool,
        createdAt: j['created_at'] as String?,
      );
}

class SourcingRequestFull {
  final int id;
  final String title;
  final String description;
  final String status;
  final String? imageUrl;
  final String? videoUrl;
  final String? budget;
  final int quantity;
  SourcingRequestFull({
    required this.id,
    required this.title,
    required this.description,
    required this.status,
    this.imageUrl,
    this.videoUrl,
    this.budget,
    required this.quantity,
  });
  factory SourcingRequestFull.fromJson(Map<String, dynamic> j) => SourcingRequestFull(
        id: j['id'] as int,
        title: (j['title'] ?? '') as String,
        description: (j['description'] ?? '') as String,
        status: (j['status'] ?? 'open') as String,
        imageUrl: j['image_url'] as String?,
        videoUrl: j['video_url'] as String?,
        budget: j['budget'] as String?,
        quantity: (j['quantity'] ?? 1) as int,
      );
}

class SourcingThread {
  final int id;
  final String vendor;
  final String? message;
  final double? price;
  final String? currency;
  SourcingThread({required this.id, required this.vendor, this.message, this.price, this.currency});
  factory SourcingThread.fromJson(Map<String, dynamic> j) => SourcingThread(
        id: j['id'] as int,
        vendor: (j['vendor'] ?? 'Vendor') as String,
        message: j['message'] as String?,
        price: j['price'] == null ? null : (j['price'] as num).toDouble(),
        currency: j['currency'] as String?,
      );
}

class ChatMessage {
  final int id;
  final bool mine;
  final String? sender;
  final String? body;
  final String? time;
  final String? listingName;
  final String? listingPrice;
  ChatMessage({required this.id, required this.mine, this.sender, this.body, this.time, this.listingName, this.listingPrice});
  factory ChatMessage.fromJson(Map<String, dynamic> j) {
    final listing = j['listing'] is Map ? j['listing'] as Map : null;
    return ChatMessage(
      id: j['id'] as int,
      mine: (j['mine'] ?? false) as bool,
      sender: j['sender'] as String?,
      body: j['body'] as String?,
      time: j['time'] as String?,
      listingName: listing?['name'] as String?,
      listingPrice: listing?['price'] as String?,
    );
  }
}
